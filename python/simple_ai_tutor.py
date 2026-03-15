from flask import Flask, request, jsonify
from flask_cors import CORS
import httpx
import json

app = Flask(__name__)
CORS(app)

# Your Gemini API key - get for free from https://aistudio.google.com/app/apikey
GEMINI_API_KEY = "YOUR_GEMINI_API_KEY_HERE"  # Replace with your actual key

# Gemini API endpoint
GEMINI_URL = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={GEMINI_API_KEY}"

# Engineering system prompt
SYSTEM_PROMPT = """You are an expert engineering tutor. Answer questions about:
- Computer Science (programming, algorithms, data structures)
- Electrical Engineering (circuits, electronics, communication)
- Mechanical Engineering (thermodynamics, fluids, machines)
- Civil Engineering (structures, construction, materials)
- Mathematics (calculus, algebra, differential equations)

Provide clear explanations with formulas and examples when relevant."""

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        user_message = data.get('message', '').strip()
        
        if not user_message:
            return jsonify({'error': 'Please provide a question'}), 400
        
        # Prepare the request for Gemini API
        payload = {
            "contents": [{
                "parts": [{
                    "text": f"{SYSTEM_PROMPT}\n\nStudent Question: {user_message}"
                }]
            }]
        }
        
        # Make API call
        response = httpx.post(
            GEMINI_URL,
            json=payload,
            timeout=30.0
        )
        
        if response.status_code == 200:
            result = response.json()
            answer = result['candidates'][0]['content']['parts'][0]['text']
            
            return jsonify({
                'response': answer,
                'model': 'Gemini Pro',
                'status': 'success'
            })
        else:
            return jsonify({
                'error': f'API Error: {response.status_code}',
                'response': "I'm having trouble connecting. Please check your API key."
            }), 500
            
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'model': 'Gemini Pro',
        'api': 'configured'
    })

if __name__ == '__main__':
    print("\n" + "="*70)
    print("🤖 SIMPLE ENGINEERING AI TUTOR")
    print("="*70)
    print("✅ Using Gemini API - No compilation needed!")
    print("✅ Get your free API key from:")
    print("   https://aistudio.google.com/app/apikey")
    print("="*70)
    print("🚀 Server running at: http://localhost:5000")
    print("="*70)
    
    app.run(host='0.0.0.0', port=5000, debug=True)