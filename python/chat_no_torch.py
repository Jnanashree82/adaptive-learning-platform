from flask import Flask, request, jsonify
from flask_cors import CORS
import random
import json

app = Flask(__name__)
CORS(app)

# Engineering knowledge base - no PyTorch needed!
engineering_data = {
    "computer_science": {
        "python": "Python is a beginner-friendly programming language great for AI and data science.",
        "java": "Java is an object-oriented programming language used for enterprise applications.",
        "algorithm": "An algorithm is a step-by-step procedure for solving a problem.",
        "data_structure": "Data structures like arrays, lists, and trees organize data efficiently."
    },
    "electrical": {
        "ohm's law": "Ohm's Law: V = I × R (Voltage = Current × Resistance)",
        "capacitor": "A capacitor stores electrical energy in an electric field.",
        "circuit": "A circuit is a closed path where electricity flows.",
        "resistor": "Resistors limit current flow and divide voltage."
    },
    "mechanical": {
        "newton": "Newton's Second Law: F = ma (Force = mass × acceleration)",
        "torque": "Torque is rotational force: τ = r × F",
        "thermodynamics": "Thermodynamics studies heat, work, and energy transfer."
    },
    "civil": {
        "beam": "Beams bend under load. Bending moment = force × distance",
        "concrete": "Concrete is strong in compression but needs steel reinforcement for tension.",
        "foundation": "Foundations transfer building loads to the ground safely."
    },
    "math": {
        "derivative": "A derivative measures the rate of change of a function.",
        "integral": "An integral calculates the area under a curve.",
        "pythagorean": "Pythagorean theorem: a² + b² = c²"
    }
}

def get_response(question):
    """Simple response system without ML"""
    question = question.lower()
    
    # Check each category
    for category, topics in engineering_data.items():
        for keyword, response in topics.items():
            if keyword in question:
                return response
    
    # Default responses
    defaults = [
        "I can help with engineering topics! Ask about programming, circuits, mechanics, or math.",
        "What engineering subject are you studying? Try asking about Python, Ohm's Law, or Newton's laws.",
        "Feel free to ask me any engineering question - I'm here to help!"
    ]
    return random.choice(defaults)

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        message = data.get('message', '')
        response = get_response(message)
        
        return jsonify({
            'response': response,
            'model': 'knowledge-base',
            'status': 'success'
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health')
def health():
    return jsonify({
        'status': 'healthy',
        'model': 'No PyTorch needed!',
        'topics': list(engineering_data.keys())
    })

if __name__ == '__main__':
    print("="*60)
    print("🤖 ENGINEERING AI ASSISTANT (NO PYTORCH)")
    print("="*60)
    print("✅ No PyTorch required - runs immediately!")
    print("✅ Topics loaded: Computer Science, Electrical, Mechanical, Civil, Math")
    print("="*60)
    print("🚀 Server running at: http://localhost:5000")
    print("Press Ctrl+C to stop")
    print("="*60)
    app.run(host='0.0.0.0', port=5000, debug=True)