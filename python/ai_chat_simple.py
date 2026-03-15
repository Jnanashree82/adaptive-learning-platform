from flask import Flask, request, jsonify
from flask_cors import CORS
import random

app = Flask(__name__)
CORS(app)  # This allows your website to talk to the Python server

# Engineering knowledge base
responses = {
    "computer science": [
        "Python is a high-level programming language great for beginners.",
        "An algorithm is a step-by-step procedure for solving a problem.",
        "Data structures like arrays and linked lists help organize data efficiently.",
        "Object-oriented programming uses classes and objects to structure code."
    ],
    "programming": [
        "Variables store data values in programming.",
        "Functions are reusable blocks of code that perform specific tasks.",
        "Loops (for, while) repeat code multiple times.",
        "Conditional statements (if-else) make decisions in code."
    ],
    "python": [
        "Python uses indentation to define code blocks.",
        "You can create variables just by assigning values: x = 5",
        "Python lists: my_list = [1, 2, 3, 4]",
        "Python dictionaries store key-value pairs."
    ],
    "electrical": [
        "Ohm's Law: V = I × R (Voltage = Current × Resistance)",
        "A capacitor stores electrical energy temporarily.",
        "An inductor stores energy in a magnetic field.",
        "Kirchhoff's laws deal with current and voltage in circuits."
    ],
    "mechanical": [
        "Newton's Second Law: F = ma (Force = mass × acceleration)",
        "Torque is the rotational equivalent of force.",
        "Thermodynamics studies heat, work, and energy.",
        "Stress = Force/Area, Strain = Change in length/Original length"
    ],
    "civil": [
        "Beams bend under load - the bending moment determines stress.",
        "Concrete is strong in compression, steel in tension.",
        "A foundation transfers building loads to the ground.",
        "The water cycle involves evaporation, condensation, and precipitation."
    ],
    "math": [
        "The derivative measures rate of change.",
        "The integral finds area under a curve.",
        "Pythagorean theorem: a² + b² = c²",
        "In linear algebra, matrices represent linear transformations."
    ]
}

@app.route('/chat', methods=['POST', 'OPTIONS'])
def chat():
    # Handle preflight requests
    if request.method == 'OPTIONS':
        return '', 200
        
    try:
        data = request.json
        user_message = data.get('message', '').lower()
        
        print(f"Received message: {user_message}")
        
        # Check each category
        for category, category_responses in responses.items():
            if category in user_message:
                return jsonify({
                    'response': random.choice(category_responses),
                    'category': category
                })
        
        # Check for specific keywords
        if any(word in user_message for word in ['code', 'program', 'python', 'java', 'c++', 'javascript']):
            return jsonify({'response': random.choice(responses["programming"])})
        
        if any(word in user_message for word in ['circuit', 'voltage', 'current', 'resistor', 'capacitor']):
            return jsonify({'response': random.choice(responses["electrical"])})
        
        if any(word in user_message for word in ['force', 'motion', 'newton', 'energy', 'torque']):
            return jsonify({'response': random.choice(responses["mechanical"])})
        
        if any(word in user_message for word in ['beam', 'structure', 'concrete', 'building', 'construction']):
            return jsonify({'response': random.choice(responses["civil"])})
        
        if any(word in user_message for word in ['calculate', 'equation', 'formula', 'theorem', 'derivative', 'integral']):
            return jsonify({'response': random.choice(responses["math"])})
        
        # Default response for general questions
        default_responses = [
            "I can help you with engineering topics! Try asking about programming, circuits, mechanics, or math.",
            "What engineering subject are you studying? I specialize in all engineering disciplines.",
            "You can ask me about computer science, electrical engineering, mechanical engineering, civil engineering, or mathematics.",
            "I'm your engineering study assistant. Feel free to ask me any technical question!"
        ]
        
        return jsonify({
            'response': random.choice(default_responses)
        })
    
    except Exception as e:
        print(f"Error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET', 'OPTIONS'])
def health():
    if request.method == 'OPTIONS':
        return '', 200
    return jsonify({'status': 'healthy', 'message': 'AI Assistant is running'})

if __name__ == '__main__':
    print("=" * 60)
    print("🤖 ENGINEERING AI ASSISTANT".center(60))
    print("=" * 60)
    print("✅ Server starting...")
    print("✅ Topics loaded:")
    print("   - Computer Science")
    print("   - Programming")
    print("   - Python")
    print("   - Electrical Engineering")
    print("   - Mechanical Engineering")
    print("   - Civil Engineering")
    print("   - Mathematics")
    print("=" * 60)
    print("🚀 Server running at: http://localhost:5000")
    print("📝 API endpoints:")
    print("   - POST /chat  (send messages)")
    print("   - GET  /health (check status)")
    print("=" * 60)
    print("Press Ctrl+C to stop the server")
    print("=" * 60)
    
    app.run(host='0.0.0.0', port=5000, debug=True)