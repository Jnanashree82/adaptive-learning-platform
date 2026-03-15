from flask import Flask, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route('/')
def home():
    return "✅ Server is running! Use /health to check status"

@app.route('/health')
def health():
    return jsonify({
        "status": "healthy",
        "message": "Server is connected!",
        "port": 5000
    })

@app.route('/test')
def test():
    return jsonify({
        "response": "✅ Connection successful!",
        "timestamp": "Server is responding"
    })

if __name__ == '__main__':
    print("\n" + "="*60)
    print("✅ SIMPLE WORKING SERVER")
    print("="*60)
    print("📌 Server started successfully!")
    print("📌 Try these URLs in your browser:")
    print("   http://localhost:5000/")
    print("   http://localhost:5000/health")
    print("   http://localhost:5000/test")
    print("="*60)
    print("🚀 Press Ctrl+C to stop the server")
    print("="*60)
    
    app.run(host='0.0.0.0', port=5000, debug=True)