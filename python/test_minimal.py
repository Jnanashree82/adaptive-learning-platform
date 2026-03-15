from flask import Flask, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route('/health')
def health():
    return jsonify({"status": "working", "message": "Server is running!"})

@app.route('/test')
def test():
    return jsonify({"response": "Connection successful!"})

if __name__ == '__main__':
    print("="*50)
    print("TEST SERVER STARTING")
    print("="*50)
    print("Try these URLs:")
    print("  http://localhost:5000/health")
    print("  http://127.0.0.1:5000/test")
    print("="*50)
    app.run(host='0.0.0.0', port=5000, debug=True)