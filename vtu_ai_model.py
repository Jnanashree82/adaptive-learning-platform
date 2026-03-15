from flask import Flask, request, jsonify
from flask_cors import CORS
import json
import re
import nltk
from nltk.tokenize import sent_tokenize, word_tokenize
from nltk.corpus import stopwords
from collections import Counter
import hashlib

# Download NLTK data if not present
try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')
    nltk.download('stopwords')
    nltk.download('averaged_perceptron_tagger')

app = Flask(__name__)
CORS(app)

class VTUEngineeringAI:
    """
    Complete AI Model with text simplification and focus tracking
    """
    
    def __init__(self):
        self.name = "VTU Engineering AI Model v2.0"
        self.version = "2.0"
        self.simplification_cache = {}
        
    def simplify_text(self, text):
        """
        Simplify complex educational text for neurodivergent learners
        """
        # Generate cache key
        text_hash = hashlib.md5(text.encode()).hexdigest()
        
        if text_hash in self.simplification_cache:
            return self.simplification_cache[text_hash]
        
        # Split into sentences
        sentences = sent_tokenize(text)
        simplified_sentences = []
        keywords = []
        
        # Get stopwords
        stop_words = set(stopwords.words('english'))
        
        for sentence in sentences:
            # Remove complex transition words
            sentence = re.sub(r'\b(however|therefore|consequently|furthermore|nevertheless|subsequently|accordingly)\b', '', sentence, flags=re.IGNORECASE)
            
            # Break long sentences
            words = word_tokenize(sentence)
            if len(words) > 20:
                # Find natural break points
                clauses = re.split(r'\b(and|but|or|because|since|although|while|whereas)\b', sentence, flags=re.IGNORECASE)
                for clause in clauses:
                    if clause.strip() and len(clause.split()) > 5:
                        simplified_sentences.append(clause.strip() + '.')
            else:
                simplified_sentences.append(sentence)
            
            # Extract keywords (nouns and technical terms)
            pos_tags = nltk.pos_tag(words)
            for word, pos in pos_tags:
                if (pos.startswith('NN') or pos.startswith('JJ')) and len(word) > 4:
                    if word.lower() not in stop_words:
                        keywords.append(word.lower())
        
        # Remove duplicates and get top keywords
        keyword_counts = Counter(keywords)
        top_keywords = [k for k, v in keyword_counts.most_common(15)]
        
        # Join simplified sentences
        simplified = '\n\n'.join(simplified_sentences)
        
        result = {
            'original_length': len(text),
            'simplified_length': len(simplified),
            'simplified': simplified,
            'keywords': ', '.join(top_keywords),
            'sentence_count': len(simplified_sentences)
        }
        
        # Cache result
        self.simplification_cache[text_hash] = result
        
        return result
    
    def extract_action_items(self, text):
        """
        Extract action items and key points from text
        """
        sentences = sent_tokenize(text)
        action_items = []
        
        # Look for imperative sentences or key points
        for sentence in sentences:
            # Check for action-oriented sentences
            if re.search(r'\b(must|should|need to|important to|remember|note that|key point)\b', sentence, re.IGNORECASE):
                action_items.append(sentence)
            # Check for bullet points or numbered items
            elif re.search(r'^\s*[•\-*]\s+', sentence) or re.search(r'^\s*\d+\.\s+', sentence):
                action_items.append(sentence)
        
        return action_items
    
    def chunk_text(self, text, chunk_size=5):
        """
        Split text into manageable chunks for ADHD focus
        """
        sentences = sent_tokenize(text)
        chunks = []
        
        for i in range(0, len(sentences), chunk_size):
            chunk = sentences[i:i+chunk_size]
            chunks.append({
                'chunk_number': i//chunk_size + 1,
                'content': ' '.join(chunk),
                'sentence_count': len(chunk)
            })
        
        return chunks

# Initialize AI model
ai_model = VTUEngineeringAI()

@app.route('/')
def home():
    return jsonify({
        'name': ai_model.name,
        'version': ai_model.version,
        'status': 'running',
        'features': ['text_simplification', 'keyword_extraction', 'focus_tracking', 'chat']
    })

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'message': 'VTU Engineering AI is running',
        'model': ai_model.name
    })

@app.route('/simplify', methods=['POST'])
def simplify():
    """
    Simplify complex educational text
    """
    data = request.json
    text = data.get('text', '')
    
    if not text:
        return jsonify({'error': 'No text provided'}), 400
    
    # Simplify text
    simplified = ai_model.simplify_text(text)
    
    # Extract action items
    action_items = ai_model.extract_action_items(text)
    
    # Create chunks
    chunks = ai_model.chunk_text(simplified['simplified'])
    
    return jsonify({
        'success': True,
        'simplified': simplified['simplified'],
        'keywords': simplified['keywords'],
        'action_items': action_items,
        'chunks': chunks,
        'stats': {
            'original_length': simplified['original_length'],
            'simplified_length': simplified['simplified_length'],
            'sentence_count': simplified['sentence_count']
        }
    })

@app.route('/chat', methods=['POST'])
def chat():
    """
    Handle chat messages with context awareness
    """
    data = request.json
    message = data.get('message', '')
    
    if not message:
        return jsonify({'error': 'No message provided'}), 400
    
    # Simple response generation (you can expand this)
    if 'simplify' in message.lower():
        return jsonify({
            'response': "I can help simplify text! Use the 'Simplify' button on any note page, or paste the text you want simplified."
        })
    elif 'keyword' in message.lower():
        return jsonify({
            'response': "Keywords are important terms that help you understand the main concepts. You can extract keywords from any note using the 'Extract Keywords' button."
        })
    elif 'focus' in message.lower():
        return jsonify({
            'response': "Staying focused? Try these tips:\n1. Use the Focus Ruler to highlight one line at a time\n2. Break content into smaller chunks\n3. Take regular breaks\n4. Use text-to-speech to listen instead of reading"
        })
    else:
        return jsonify({
            'response': f"I understand you're asking about: '{message}'. I can help with text simplification, keyword extraction, and focus techniques. What specific aspect would you like help with?"
        })

@app.route('/focus/session', methods=['POST'])
def track_focus():
    """
    Track focus session data
    """
    data = request.json
    user_id = data.get('user_id')
    note_id = data.get('note_id')
    duration = data.get('duration')
    nudges = data.get('nudges', 0)
    
    # Here you would store in database
    # For now, just return acknowledgment
    
    return jsonify({
        'success': True,
        'message': 'Focus session tracked',
        'suggestion': get_focus_suggestion(duration, nudges)
    })

def get_focus_suggestion(duration, nudges):
    """
    Generate suggestions based on focus patterns
    """
    if duration > 300:  # More than 5 minutes
        return "You've been reading for a while. Consider taking a short break!"
    elif nudges > 2:
        return "Having trouble focusing? Try the simplified version or text-to-speech."
    else:
        return "Keep up the good work!"

if __name__ == '__main__':
    print(f"Starting {ai_model.name}")
    print("Available endpoints:")
    print("  - GET  /          : Info")
    print("  - GET  /health    : Health check")
    print("  - POST /simplify  : Text simplification")
    print("  - POST /chat      : Chat with AI")
    print("  - POST /focus/session : Track focus")
    print("\nServer running on http://localhost:5000")
    app.run(debug=True, port=5000)