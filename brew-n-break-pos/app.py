from flask import Flask, jsonify, request, session
from flask_cors import CORS
import mysql.connector
import bcrypt

app = Flask(__name__)
app.secret_key = 'brew_n_break_secret_key'
CORS(app, supports_credentials=True, origins=['http://localhost', 'http://127.0.0.1'])

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'brew_n_break'
}

def get_db():
    return mysql.connector.connect(**DB_CONFIG)


@app.route('/api/login', methods=['POST'])
def login():
    data = request.json
    username = data.get('username', '').strip()
    password = data.get('password', '')
    if not username or not password:
        return jsonify({'success': False, 'message': 'Please fill in both fields.'})
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, name, username, password, role FROM users WHERE username = %s AND status = 'Active' LIMIT 1", (username,))
        user = cursor.fetchone()
        cursor.close()
        conn.close()
        if user and bcrypt.checkpw(password.encode('utf-8'), user['password'].encode('utf-8')):
            session['user_id']  = user['id']
            session['username'] = user['username']
            session['name']     = user['name']
            session['role']     = user['role']
            redirect = 'brew-n-break-pos/staff.php' if user['role'].lower() == 'staff' else 'brew-n-break-pos/dashboard.php'
            return jsonify({'success': True, 'redirect': redirect})
        return jsonify({'success': False, 'message': 'Invalid username or password.'})
    except Exception as e:
        return jsonify({'success': False, 'message': 'Server error.'})


@app.route('/api/billiard-status', methods=['GET'])
def billiard_status():
    all_tables = ['Outdoor 1', 'Outdoor 2', 'Outdoor 3', 'Indoor 1']
    table_rows = []
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT bs.table_name, bs.status, bs.customer_name,
                   GREATEST(0, TIME_TO_SEC(TIMEDIFF(bs.end_time, TIME(NOW())))) AS secs_left
            FROM billiard_sessions bs
            INNER JOIN (
                SELECT table_name, MAX(id) as mid FROM billiard_sessions GROUP BY table_name
            ) latest ON bs.id = latest.mid
        """)
        rows = cursor.fetchall()
        cursor.close()
        conn.close()

        live_map = {r['table_name']: r for r in rows}
        for tbl in all_tables:
            if tbl in live_map:
                s = live_map[tbl]
                status_lower = s['status'].lower()
                if status_lower in ['ongoing', 'start']:
                    secs = int(s['secs_left'])
                    hours_left = f"{secs//3600:02d}:{(secs%3600)//60:02d}:{secs%60:02d}" if secs > 0 else 'Overtime'
                    table_rows.append({'table_name': tbl, 'status': s['status'], 'hours_left': hours_left, 'customer': s['customer_name'] or ''})
                elif status_lower == 'reserved':
                    table_rows.append({'table_name': tbl, 'status': 'Reserved', 'hours_left': '–', 'customer': s['customer_name'] or ''})
                else:
                    table_rows.append({'table_name': tbl, 'status': 'Available', 'hours_left': '–', 'customer': ''})
            else:
                table_rows.append({'table_name': tbl, 'status': 'Available', 'hours_left': '–', 'customer': ''})

        return jsonify({'success': True, 'tables': table_rows})
    except Exception as e:
        return jsonify({'success': False, 'tables': []})


@app.route('/api/dashboard-stats', methods=['GET'])
def dashboard_stats():
    try:
        conn = get_db()
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")
        total_orders = cursor.fetchone()[0]
        cursor.execute("SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time,start_time))/3600),0) FROM billiard_sessions WHERE DATE(created_at)=CURDATE() AND status IN ('Done','Ongoing','Start')")
        total_hours = round(float(cursor.fetchone()[0]), 1)
        cursor.execute("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()")
        total_bookings = cursor.fetchone()[0]
        cursor.execute("SELECT COALESCE((SELECT SUM(total_amount) FROM orders WHERE DATE(created_at)=CURDATE()),0) + COALESCE((SELECT SUM(amount) FROM billiard_sessions WHERE DATE(created_at)=CURDATE()),0) + COALESCE((SELECT SUM(DATEDIFF(check_out,check_in)*3500) FROM bookings WHERE DATE(created_at)=CURDATE()),0) AS total")
        total_revenue = float(cursor.fetchone()[0])
        cursor.close()
        conn.close()
        return jsonify({
            'success': True,
            'orders': total_orders,
            'hours': total_hours,
            'bookings': total_bookings,
            'revenue': total_revenue
        })
    except Exception as e:
        return jsonify({'success': False})


@app.route('/api/sessions', methods=['GET'])
def get_sessions():
    table = request.args.get('table', 'all')
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        if table == 'all':
            cursor.execute("SELECT id, session_code, customer_name, table_name, start_time, end_time, amount, status, created_at FROM billiard_sessions ORDER BY created_at DESC")
        else:
            cursor.execute("SELECT id, session_code, customer_name, table_name, start_time, end_time, amount, status, created_at FROM billiard_sessions WHERE table_name=%s ORDER BY created_at DESC", (table,))
        sessions = cursor.fetchall()
        cursor.close()
        conn.close()
        for s in sessions:
            for key in ['start_time', 'end_time', 'created_at']:
                if s[key]:
                    s[key] = str(s[key])
            s['amount'] = float(s['amount'])
        return jsonify({'success': True, 'sessions': sessions})
    except Exception as e:
        return jsonify({'success': False, 'sessions': []})


@app.route('/api/transactions', methods=['GET'])
def get_transactions():
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT o.id, o.order_code AS transaction_code,
                GROUP_CONCAT(CONCAT(oi.quantity,'x ',p.name) ORDER BY p.name SEPARATOR ', ') AS description,
                o.total_amount, o.status, o.type, o.created_at, 'cafe' AS source
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN products p ON p.id = oi.product_id
            GROUP BY o.id
        """)
        transactions = cursor.fetchall()
        cursor.execute("""
            SELECT bs.id, bs.session_code AS transaction_code,
                CONCAT(bs.table_name, ' — ', bs.customer_name) AS description,
                bs.amount AS total_amount, bs.status, 'Billiards' AS type, bs.created_at, 'billiard' AS source
            FROM billiard_sessions bs
        """)
        transactions += cursor.fetchall()
        cursor.close()
        conn.close()
        for t in transactions:
            t['created_at'] = str(t['created_at'])
            t['total_amount'] = float(t['total_amount'])
        transactions.sort(key=lambda x: x['created_at'], reverse=True)
        return jsonify({'success': True, 'transactions': transactions})
    except Exception as e:
        return jsonify({'success': False, 'transactions': []})


@app.route('/api/notifications', methods=['GET'])
def get_notifications():
    alerts = []
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT id, table_name, customer_name, end_time,
                GREATEST(0, TIME_TO_SEC(TIMEDIFF(end_time, TIME(NOW())))) AS secs_left
            FROM billiard_sessions
            WHERE status IN ('Ongoing','Start')
            HAVING secs_left <= 600
            ORDER BY secs_left ASC
        """)
        for row in cursor.fetchall():
            secs = int(row['secs_left'])
            alerts.append({
                'id': row['id'],
                'type': 'expired' if secs == 0 else ('warning' if secs <= 300 else 'info'),
                'title': f"{row['table_name']} {'Session Expired' if secs == 0 else '— 5 min warning'}",
                'message': f"{row['customer_name']} · Ends {str(row['end_time'])}",
                'secs_left': secs
            })
        cursor.close()
        conn.close()
    except Exception as e:
        pass
    return jsonify({'alerts': alerts})


@app.route('/api/session-action', methods=['POST'])
def session_action():
    data = request.json
    action = data.get('action', '')
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        if action == 'add':
            code   = data.get('code', '').strip()
            name   = data.get('name', '').strip()
            start  = data.get('start', '')
            end    = data.get('end', '')
            table  = data.get('table', '')
            amount = float(data.get('amount', 0))
            status = data.get('status', 'Start')
            if not code or not name or not start or not end:
                return jsonify({'success': False, 'message': 'Required fields missing.'})
            cursor.execute(
                "INSERT INTO billiard_sessions (session_code, customer_name, table_name, start_time, end_time, amount, status, created_at) VALUES (%s,%s,%s,%s,%s,%s,%s,NOW())",
                (code, name, table, start, end, amount, status)
            )
            conn.commit()
        elif action == 'edit_status':
            status = data.get('status', 'Start')
            id_val = int(data.get('id', 0))
            cursor.execute("UPDATE billiard_sessions SET status=%s WHERE id=%s", (status, id_val))
            cursor.execute("SELECT table_name FROM billiard_sessions WHERE id=%s", (id_val,))
            row = cursor.fetchone()
            if row and row['table_name']:
                tbl_status = {'Start': 'Occupied', 'Ongoing': 'Occupied', 'Reserved': 'Reserved'}.get(status, 'Available')
                cursor.execute("UPDATE billiard_tables SET status=%s WHERE table_name=%s", (tbl_status, row['table_name']))
            conn.commit()
        elif action == 'delete':
            cursor.execute("DELETE FROM billiard_sessions WHERE id=%s", (int(data.get('id', 0)),))
            conn.commit()
        elif action == 'next_code':
            cursor.execute("SELECT session_code FROM billiard_sessions ORDER BY id DESC LIMIT 1")
            row = cursor.fetchone()
            last = row['session_code'] if row else ''
            import re
            m = re.match(r'^([A-Za-z]+)(\d+)$', last)
            if m:
                code = m.group(1) + str(int(m.group(2)) + 1).zfill(len(m.group(2)))
            else:
                code = 'BT001'
            cursor.close()
            conn.close()
            return jsonify({'success': True, 'code': code})
        cursor.close()
        conn.close()
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})


@app.route('/api/transaction-action', methods=['POST'])
def transaction_action():
    data = request.json
    action = data.get('action', '')
    try:
        conn = get_db()
        cursor = conn.cursor(dictionary=True)
        if action == 'checkout':
            for item in data.get('items', []):
                source = item.get('source', '')
                item_id = int(item.get('id', 0))
                if source == 'cafe' and item_id:
                    cursor.execute("UPDATE orders SET status='Done' WHERE id=%s", (item_id,))
                elif source == 'billiard' and item_id:
                    cursor.execute("SELECT status, table_name FROM billiard_sessions WHERE id=%s", (item_id,))
                    row = cursor.fetchone()
                    if row and row['status'] != 'Reserved':
                        cursor.execute("UPDATE billiard_sessions SET status='Done' WHERE id=%s", (item_id,))
                        if row['table_name']:
                            cursor.execute("UPDATE billiard_tables SET status='Available' WHERE table_name=%s", (row['table_name'],))
            conn.commit()
        elif action == 'edit':
            source = data.get('source', '')
            id_val = int(data.get('id', 0))
            status = data.get('status', '')
            amount = float(data.get('amount', 0))
            if source == 'cafe':
                cursor.execute("UPDATE orders SET status=%s, total_amount=%s WHERE id=%s", (status, amount, id_val))
            elif source == 'billiard':
                cursor.execute("UPDATE billiard_sessions SET status=%s, amount=%s WHERE id=%s", (status, amount, id_val))
            conn.commit()
        elif action == 'delete':
            source = data.get('source', '')
            id_val = int(data.get('id', 0))
            if source == 'cafe':
                cursor.execute("DELETE FROM order_items WHERE order_id=%s", (id_val,))
                cursor.execute("DELETE FROM orders WHERE id=%s", (id_val,))
            elif source == 'billiard':
                cursor.execute("DELETE FROM billiard_sessions WHERE id=%s", (id_val,))
            conn.commit()
        cursor.close()
        conn.close()
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})


if __name__ == '__main__':
    app.run(debug=True, port=5000)
