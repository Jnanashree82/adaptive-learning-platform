from flask import Flask, request, jsonify
from flask_cors import CORS
import time

app = Flask(__name__)
# Allow all origins with proper CORS support
CORS(app, origins=['http://localhost', 'http://127.0.0.1', 'http://localhost:80', 'http://localhost:3307'], 
     allow_headers=['Content-Type'], 
     methods=['GET', 'POST', 'OPTIONS'])

# Simple knowledge base
responses = {
    "python": "Python is a programming language great for beginners! It's easy to read and write.",
    "java": "Java is an object-oriented programming language used for building large applications.",
    "circuit": "A circuit needs: a power source (like a battery), wires to carry electricity, and a load (like a light bulb).",
    "voltage": "Voltage (V) is electrical pressure - like water pressure in a pipe. It pushes electricity through circuits.",
    "current": "Current (I) is the flow of electricity - like water flowing through a pipe. Measured in amperes.",
    "resistance": "Resistance (R) opposes current flow - like a narrow pipe restricts water flow. Measured in ohms.",
    "ohm": "Ohm's Law: V = I × R (Voltage = Current × Resistance). This is the fundamental law of electricity!",
    "newton": "Newton's Second Law: F = ma (Force = mass × acceleration). This explains how motion works!",
    "force": "Force = mass × acceleration (F = ma). Push or pull on an object causes it to accelerate.",
    "beam": "Beams bend under load - like a diving board! The bending moment = force × distance.",
    "concrete": "Concrete is strong when squeezed (compression) but weak when pulled (tension). That's why we add steel reinforcement!",
    "derivative": "A derivative measures the rate of change - like how fast a car's speed is changing.",
    "integral": "An integral calculates the area under a curve - like finding total distance from speed over time.",
    "hello": "Hello! I'm your engineering tutor. Ask me about programming, circuits, mechanics, or math!",
    "hi": "Hi there! Ready to learn some engineering? What topic interests you?",
}

@app.route('/health', methods=['GET', 'OPTIONS'])
def health():
    if request.method == 'OPTIONS':
        return '', 200
    return jsonify({
        "status": "connected", 
        "server": "running",
        "time": time.time()
    })

@app.route('/test', methods=['GET', 'OPTIONS'])
def test():
    if request.method == 'OPTIONS':
        return '', 200
    return jsonify({
        "message": "Server is working!",
        "endpoints": ["/health", "/test", "/chat (POST)"]
    })

@app.route('/chat', methods=['POST', 'OPTIONS'])
def chat():
    # Handle preflight OPTIONS request
    if request.method == 'OPTIONS':
        return '', 200
    
    try:
        # Get JSON data
        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON data received"}), 400
        
        message = data.get('message', '').lower().strip()
        print(f"Received message: '{message}'")  # Debug log
        
        if not message:
            return jsonify({"error": "Empty message"}), 400
        
        # Check for exact matches first
        if message in responses:
            return jsonify({"response": responses[message]})
        
        # Check for keywords
        for key, response in responses.items():
            if key in message:
                return jsonify({"response": response})
        
        # Default response
        default_msg = f"I'm your engineering tutor! I can help with: Python, circuits, Ohm's Law, Newton's laws, and more. You asked: '{message}'. Could you be more specific about what engineering topic you want to learn?"
        return jsonify({"response": default_msg})
        
    except Exception as e:
        print(f"Error in chat endpoint: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/debug', methods=['GET'])
def debug():
    """Debug endpoint to check server status"""
    return jsonify({
        "server": "running",
        "port": 5000,
        "time": time.time(),
        "endpoints": ["/health", "/test", "/chat", "/debug"],
        "status": "ok"
    })

if __name__ == '__main__':
    print("\n" + "="*70)
    print("✅ ENGINEERING CHAT SERVER - RUNNING")
    print("="*70)
    print("📌 Server is active on: http://localhost:5000")
    print("📌 Test URLs:")
    print("   • http://localhost:5000/health")
    print("   • http://localhost:5000/test")
    print("   • http://localhost:5000/debug")
    print("📌 Chat endpoint: POST http://localhost:5000/chat")
    print("="*70)
    print("📚 Loaded topics:", ", ".join(list(responses.keys())[:10]))
    print("="*70)
    app.run(host='0.0.0.0', port=5000, debug=True)