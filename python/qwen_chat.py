from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
from transformers import AutoModelForCausalLM, AutoTokenizer
import torch
import time
import os
import gc

app = Flask(__name__)
CORS(app)  # Allow connections from your website

# Global variables for model
model = None
tokenizer = None
model_loaded = False

# Engineering-specific system prompts for different fields
SYSTEM_PROMPTS = {
    "general": """You are NeuroLearn AI, a friendly engineering tutor designed for neurodivergent students. 
    Follow these guidelines:
    1. Use simple, clear language
    2. Break down complex concepts into small steps
    3. Use bullet points for lists
    4. Be patient and encouraging
    5. If asked something outside engineering, politely redirect to engineering topics
    6. Keep responses concise but informative""",
    
    "computer_science": """You are a Computer Science tutor for neurodivergent students.
    Explain programming concepts with:
    - Simple analogies
    - Step-by-step logic
    - Real-world examples
    Avoid jargon without explanation.""",
    
    "electrical": """You are an Electrical Engineering tutor.
    Explain circuits and electronics with:
    - Visual descriptions
    - Practical applications
    - Simple analogies (water flow for current, etc.)""",
    
    "mechanical": """You are a Mechanical Engineering tutor.
    Explain mechanics and thermodynamics with:
    - Everyday examples
    - Clear cause-and-effect
    - Simple calculations step-by-step""",
    
    "civil": """You are a Civil Engineering tutor.
    Explain structures and materials with:
    - Real building examples
    - Safety-focused explanations
    - Visual descriptions""",
    
    "mathematics": """You are a Mathematics tutor.
    Explain math concepts with:
    - Step-by-step derivations
    - Visual aids in text
    - Practical applications"""
}

def detect_engineering_field(question):
    """Detect which engineering field the question belongs to"""
    question_lower = question.lower()
    
    # Computer Science keywords
    if any(word in question_lower for word in ['python', 'java', 'code', 'program', 'algorithm', 
                                                'data structure', 'function', 'variable', 'software']):
        return "computer_science"
    
    # Electrical Engineering keywords
    elif any(word in question_lower for word in ['circuit', 'voltage', 'current', 'resistor', 
                                                  'capacitor', 'ohm', 'electric', 'diode']):
        return "electrical"
    
    # Mechanical Engineering keywords
    elif any(word in question_lower for word in ['force', 'motion', 'newton', 'torque', 'energy',
                                                  'mechanics', 'thermodynamics', 'heat', 'engine']):
        return "mechanical"
    
    # Civil Engineering keywords
    elif any(word in question_lower for word in ['beam', 'structure', 'concrete', 'building',
                                                  'construction', 'load', 'foundation', 'bridge']):
        return "civil"
    
    # Mathematics keywords
    elif any(word in question_lower for word in ['calculus', 'derivative', 'integral', 'equation',
                                                  'theorem', 'matrix', 'algebra', 'geometry']):
        return "mathematics"
    
    # Default to general engineering
    return "general"

def load_model():
    """Load the Qwen2.5 model (called once at startup)"""
    global model, tokenizer, model_loaded
    
    print("🔄 Loading Qwen2.5 engineering model...")
    print("📦 This may take 2-5 minutes on first run...")
    
    try:
        # Use the 1.5B parameter version for faster responses on CPU
        # Change to "Qwen/Qwen2.5-7B-Instruct" if you have more RAM
        model_name = "Qwen/Qwen2.5-1.5B-Instruct"
        
        # Load tokenizer
        tokenizer = AutoTokenizer.from_pretrained(model_name)
        
        # Load model with optimizations for your system
        model = AutoModelForCausalLM.from_pretrained(
            model_name,
            torch_dtype=torch.float32,  # Use float32 for CPU compatibility
            low_cpu_mem_usage=True,
            device_map="auto"
        )
        
        model_loaded = True
        print("✅ Model loaded successfully!")
        print(f"📊 Model: {model_name}")
        print("🚀 Ready to answer engineering questions!")
        
    except Exception as e:
        print(f"❌ Error loading model: {str(e)}")
        print("⚠️ Using fallback mode with simple responses")
        model_loaded = False

def get_simple_fallback_response(question, field):
    """Provide simple responses if model fails to load"""
    responses = {
        "computer_science": "Computer science is about solving problems with computers. What specific topic would you like to learn about?",
        "electrical": "Electrical engineering deals with electricity, circuits, and electronics. Feel free to ask about specific concepts!",
        "mechanical": "Mechanical engineering involves machines, motion, and energy. I can explain any topic you're curious about.",
        "civil": "Civil engineering is about building and maintaining our world - from bridges to buildings. What interests you?",
        "mathematics": "Mathematics is the language of engineering. Ask me about calculus, algebra, or any math topic!",
        "general": "I'm your engineering tutor! Ask me about computer science, electrical, mechanical, civil engineering, or math."
    }
    return responses.get(field, responses["general"])

def generate_response(question, field):
    """Generate response using the model"""
    if not model_loaded:
        return get_simple_fallback_response(question, field)
    
    try:
        # Get the appropriate system prompt
        system_prompt = SYSTEM_PROMPTS.get(field, SYSTEM_PROMPTS["general"])
        
        # Format the conversation
        messages = [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": question}
        ]
        
        # Apply chat template
        text = tokenizer.apply_chat_template(
            messages,
            tokenize=False,
            add_generation_prompt=True
        )
        
        # Tokenize
        model_inputs = tokenizer([text], return_tensors="pt", padding=True)
        
        # Generate response
        with torch.no_grad():
            generated_ids = model.generate(
                model_inputs.input_ids,
                max_new_tokens=256,
                temperature=0.7,
                do_sample=True,
                top_p=0.9,
                pad_token_id=tokenizer.eos_token_id
            )
        
        # Decode response
        generated_ids = generated_ids[0][len(model_inputs.input_ids[0]):]
        response = tokenizer.decode(generated_ids, skip_special_tokens=True)
        
        return response.strip()
        
    except Exception as e:
        print(f"Generation error: {str(e)}")
        return get_simple_fallback_response(question, field)

@app.route('/chat', methods=['POST', 'OPTIONS'])
def chat():
    if request.method == 'OPTIONS':
        return '', 200
    
    start_time = time.time()
    
    try:
        data = request.json
        user_message = data.get('message', '').strip()
        
        if not user_message:
            return jsonify({'error': 'Please provide a message'}), 400
        
        # Detect engineering field
        field = detect_engineering_field(user_message)
        
        # Generate response
        response = generate_response(user_message, field)
        
        # Calculate response time
        response_time = time.time() - start_time
        
        return jsonify({
            'response': response,
            'field': field.replace('_', ' ').title(),
            'response_time': f"{response_time:.2f}s",
            'model': 'Qwen2.5 (Free)',
            'status': 'success'
        })
        
    except Exception as e:
        print(f"Error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'model_loaded': model_loaded,
        'fields': list(SYSTEM_PROMPTS.keys()),
        'message': 'Engineering AI Assistant is ready!'
    })

@app.route('/clear', methods=['POST'])
def clear_memory():
    """Clear GPU memory if needed"""
    if torch.cuda.is_available():
        torch.cuda.empty_cache()
    gc.collect()
    return jsonify({'status': 'memory cleared'})

if __name__ == '__main__':
    print("=" * 70)
    print("🤖 NEUROLEARN ENGINEERING AI ASSISTANT".center(70))
    print("=" * 70)
    print("📚 Using Qwen2.5 - Free Open Source Model")
    print("🎯 Specialized for Neurodivergent Students")
    print("-" * 70)
    
    # Load the model
    load_model()
    
    print("-" * 70)
    print("🚀 Server starting on http://localhost:5000")
    print("📝 API Endpoints:")
    print("   POST /chat  - Send engineering questions")
    print("   GET  /health - Check server status")
    print("   POST /clear - Clear memory (if needed)")
    print("=" * 70)
    print("Press Ctrl+C to stop the server")
    print("=" * 70)
    
    app.run(host='0.0.0.0', port=5000, debug=False, threaded=True)