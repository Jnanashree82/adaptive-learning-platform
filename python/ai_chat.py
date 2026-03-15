from flask import Flask, request, jsonify
from flask_cors import CORS
import torch
from transformers import AutoModelForCausalLM, AutoTokenizer
import json

app = Flask(__name__)
CORS(app)

# Load a smaller, faster model suitable for engineering questions
model_name = "microsoft/DialoGPT-medium"
tokenizer = AutoTokenizer.from_pretrained(model_name)
model = AutoModelForCausalLM.from_pretrained(model_name)

# Engineering knowledge base for faster responses
engineering_knowledge = {
    "computer science": {
        "keywords": ["programming", "algorithm", "data structure", "python", "java", "cpp"],
        "responses": {
            "what is an algorithm": "An algorithm is a step-by-step procedure for solving a problem or accomplishing a task.",
            "what is python": "Python is a high-level, interpreted programming language known for its simplicity and readability.",
            "what is data structure": "A data structure is a way of organizing and storing data for efficient access and modification."
        }
    },
    "electrical engineering": {
        "keywords": ["circuit", "voltage", "current", "resistance", "capacitor", "inductor"],
        "responses": {
            "what is ohm's law": "Ohm's law states that the current through a conductor is directly proportional to the voltage and inversely proportional to the resistance: V = IR",
            "what is a capacitor": "A capacitor is a passive electronic component that stores electrical energy in an electric field."
        }
    },
    "mathematics": {
        "keywords": ["calculus", "derivative", "integral", "matrix", "algebra", "theorem"],
        "responses": {
            "what is derivative": "A derivative measures how a function changes as its input changes. It's the slope of the function at any point.",
            "what is integral": "An integral represents the area under a curve or the accumulation of quantities."
        }
    }
}

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        user_message = data.get('message', '').lower()
        
        # Check for quick responses from knowledge base
        for category, info in engineering_knowledge.items():
            for question, answer in info['responses'].items():
                if question in user_message:
                    return jsonify({
                        'response': answer,
                        'source': 'knowledge_base',
                        'fast_response': True
                    })
        
        # Use model for complex queries
        inputs = tokenizer.encode(user_message + tokenizer.eos_token, return_tensors='pt')
        
        # Generate response
        with torch.no_grad():
            outputs = model.generate(
                inputs,
                max_length=150,
                num_return_sequences=1,
                temperature=0.7,
                pad_token_id=tokenizer.eos_token_id
            )
        
        response = tokenizer.decode(outputs[0], skip_special_tokens=True)
        
        # Remove the input message from response
        response = response.replace(user_message, '').strip()
        
        return jsonify({
            'response': response,
            'source': 'ai_model',
            'fast_response': False
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'healthy'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)