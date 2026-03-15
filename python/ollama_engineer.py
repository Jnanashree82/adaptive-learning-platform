from flask import Flask, request, jsonify
from flask_cors import CORS
import requests
import json
import time

app = Flask(__name__)
CORS(app)

# Ollama API endpoint (runs locally)
OLLAMA_URL = "http://localhost:11434/api/generate"

# Engineering system prompt for better responses
SYSTEM_PROMPT = """You are an expert engineering tutor with deep knowledge in:
- Computer Science (algorithms, data structures, programming)
- Electrical Engineering (circuits, Ohm's Law, electronics)
- Mechanical Engineering (dynamics, thermodynamics, mechanics)
- Civil Engineering (structures, materials, construction)
- Mathematics (calculus, differential equations, linear algebra)

Provide detailed, accurate answers with:
1. Core concept explanation
2. Relevant formulas (with units)
3. Step-by-step examples
4. Real-world applications
5. Simple language for neurodivergent learners"""

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        user_message = data.get('message', '').strip()
        
        if not user_message:
            return jsonify({'error': 'Please provide a question'}), 400
        
        # Prepare prompt for Ollama
        prompt = f"{SYSTEM_PROMPT}\n\nUser Question: {user_message}\n\nDetailed Answer:"
        
        # Call Ollama
        response = requests.post(OLLAMA_URL, json={
            "model": "rnj-1:8b-cloud",  # Change to your chosen model
            "prompt": prompt,
            "stream": False,
            "options": {
                "temperature": 0.7,
                "max_tokens": 1000
            }
        })
        
        if response.status_code == 200:
            result = response.json()
            answer = result.get('response', 'No response generated')
            
            return jsonify({
                'response': answer,
                'model': 'rnj-1 (STEM-optimized)',
                'status': 'success'
            })
        else:
            return jsonify({'error': f'Ollama error: {response.status_code}'}), 500
            
    except requests.exceptions.ConnectionError:
        return jsonify({'error': 'Cannot connect to Ollama. Make sure it\'s running with "ollama serve"'}), 500
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    # Check if Ollama is running
    try:
        requests.get("http://localhost:11434/api/tags", timeout=2)
        ollama_status = "connected"
    except:
        ollama_status = "disconnected"
    
    return jsonify({
        'status': 'healthy',
        'ollama': ollama_status,
        'model': 'rnj-1:8b-cloud',
        'capabilities': ['CS', 'Electrical', 'Mechanical', 'Civil', 'Math']
    })

if __name__ == '__main__':
    print("="*70)
    print("🤖 ENGINEERING AI TUTOR (OLLAMA)")
    print("="*70)
    print("📚 Model: rnj-1:8b-cloud (STEM-optimized)")
    print("🔧 Make sure Ollama is running!")
    print("   Run: ollama serve")
    print("="*70)
    app.run(host='0.0.0.0', port=5000, debug=True)