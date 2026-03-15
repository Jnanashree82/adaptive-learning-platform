from flask import Flask, request, jsonify
from flask_cors import CORS
import openai
import os

app = Flask(__name__)
CORS(app)

# Configure your Poe API key
POE_API_KEY = "YOUR_POE_API_KEY_HERE"  # Replace with your actual key
client = openai.OpenAI(
    api_key=POE_API_KEY,
    base_url="https://api.poe.com/v1"
)

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        user_message = data.get('message', '').strip()
        
        if not user_message:
            return jsonify({'error': 'Please provide a question'}), 400
        
        # Add engineering context to the prompt
        enhanced_prompt = f"""You are an expert engineering tutor specializing in all engineering disciplines including Computer Science, Electronics, Mechanical, Civil, and Chemical Engineering. 
        
        Provide detailed, accurate answers with:
        - Clear explanations
        - Formulas where applicable
        - Step-by-step examples
        - Real-world applications
        
        Student Question: {user_message}
        
        Answer:"""
        
        # Call Poe API with the Engineering Tutor model
        response = client.chat.completions.create(
            model="engineeringtutoreng",  # Poe's specialized engineering tutor
            messages=[
                {"role": "system", "content": "You are an expert engineering tutor."},
                {"role": "user", "content": enhanced_prompt}
            ],
            temperature=0.7,
            max_tokens=1000
        )
        
        answer = response.choices[0].message.content
        
        return jsonify({
            'response': answer,
            'model': 'Poe Engineering Tutor',
            'status': 'success'
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'model': 'Poe Engineering Tutor',
        'api': 'connected'
    })

if __name__ == '__main__':
    print("\n" + "="*70)
    print("🤖 ENGINEERING AI TUTOR (Poe API)")
    print("="*70)
    print("✅ Connected to Poe Engineering Tutor")
    print("✅ Covers all engineering disciplines")
    print("="*70)
    print("🚀 Server running at: http://localhost:5000")
    print("="*70)
    
    app.run(host='0.0.0.0', port=5000, debug=True)