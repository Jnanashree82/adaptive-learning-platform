from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import os

app = Flask(__name__)
CORS(app)

# Base directory for notes
NOTES_BASE_DIR = "D:/xampp/htdocs/adaptive-learning-platform/vtu_notes/"

# ============================================
# ALL ENGINEERING BRANCHES
# ============================================
branches = {
    "CSE": {
        "name": "Computer Science Engineering",
        "icon": "💻",
        "description": "Study of computers, programming, algorithms, data structures"
    },
    "ISE": {
        "name": "Information Science Engineering",
        "icon": "🌐",
        "description": "Study of information systems, web technologies, databases"
    },
    "ECE": {
        "name": "Electronics & Communication Engineering",
        "icon": "📡",
        "description": "Study of electronics, circuits, communication systems"
    },
    "EEE": {
        "name": "Electrical & Electronics Engineering",
        "icon": "⚡",
        "description": "Study of electrical machines, power systems"
    },
    "ME": {
        "name": "Mechanical Engineering",
        "icon": "🔧",
        "description": "Study of machines, thermodynamics, manufacturing"
    },
    "CE": {
        "name": "Civil Engineering",
        "icon": "🏗️",
        "description": "Study of structures, construction, materials"
    },
    "AIML": {
        "name": "Artificial Intelligence & Machine Learning",
        "icon": "🤖",
        "description": "Study of AI, ML, deep learning, neural networks"
    },
    "AIDS": {
        "name": "Artificial Intelligence & Data Science",
        "icon": "📊",
        "description": "Study of data science, analytics, AI, big data"
    },
    "CSD": {
        "name": "Computer Science & Design",
        "icon": "🎨",
        "description": "Study of CS with UI/UX design, graphics"
    },
    "CSE_CY": {
        "name": "CSE (Cyber Security)",
        "icon": "🔐",
        "description": "Study of cybersecurity, cryptography, network security"
    },
    "CSE_DS": {
        "name": "CSE (Data Science)",
        "icon": "📈",
        "description": "Study of data science, analytics, visualization"
    },
    "CSE_IOT": {
        "name": "CSE (Internet of Things)",
        "icon": "📱",
        "description": "Study of IoT, sensors, embedded systems"
    },
    "AE": {
        "name": "Aeronautical Engineering",
        "icon": "✈️",
        "description": "Study of aircraft, aerodynamics, flight mechanics"
    },
    "AU": {
        "name": "Automobile Engineering",
        "icon": "🚗",
        "description": "Study of vehicles, engines, electric vehicles"
    },
    "BT": {
        "name": "Biotechnology Engineering",
        "icon": "🧬",
        "description": "Study of biology, genetics, bioprocess engineering"
    },
    "CHE": {
        "name": "Chemical Engineering",
        "icon": "🧪",
        "description": "Study of chemical processes, plant design"
    },
    "MINING": {
        "name": "Mining Engineering",
        "icon": "⛏️",
        "description": "Study of mining, mineral processing"
    },
    "TEXTILE": {
        "name": "Textile Engineering",
        "icon": "🧵",
        "description": "Study of textiles, fibers, garments"
    },
    "MARINE": {
        "name": "Marine Engineering",
        "icon": "🚢",
        "description": "Study of ships, marine propulsion"
    },
    "AGRI": {
        "name": "Agricultural Engineering",
        "icon": "🌾",
        "description": "Study of farm machinery, irrigation"
    }
}

# ============================================
# FUNCTION TO GET SUBJECTS FROM FOLDERS
# ============================================
def get_subjects_from_folders(branch, semester):
    """Read subject names from the notes folder structure"""
    subjects = []
    folder_path = os.path.join(NOTES_BASE_DIR, branch, f"Semester{semester}")
    
    if os.path.exists(folder_path):
        # Get all text files in the semester folder
        files = os.listdir(folder_path)
        for file in files:
            if file.endswith('.txt'):
                # Remove .txt extension to get subject name
                subject_name = file.replace('.txt', '')
                subjects.append({
                    "code": subject_name.replace(' ', '').upper(),
                    "name": subject_name,
                    "credits": 4,
                    "file": file
                })
    
    return subjects

# ============================================
# API ROUTES
# ============================================

@app.route('/')
def home():
    return jsonify({
        "message": "VTU Notes API",
        "branches": len(branches),
        "endpoints": {
            "/branches": "List all branches",
            "/subjects/<branch>/<semester>": "Get subjects for branch and semester",
            "/notes/<branch>/<semester>/<subject>": "Get notes for subject",
            "/download/<branch>/<semester>/<subject>": "Download notes"
        }
    })

@app.route('/branches', methods=['GET'])
def get_branches():
    return jsonify(branches)

@app.route('/subjects/<branch>/<int:semester>', methods=['GET'])
def get_subjects(branch, semester):
    subjects_list = get_subjects_from_folders(branch, semester)
    if subjects_list:
        return jsonify(subjects_list)
    return jsonify({"error": "No subjects found", "subjects": []}), 404

@app.route('/notes/<branch>/<int:semester>/<subject>', methods=['GET'])
def get_notes(branch, semester, subject):
    file_path = os.path.join(NOTES_BASE_DIR, branch, f"Semester{semester}", f"{subject}.txt")
    
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Parse the content into units and topics (assuming specific format)
        notes_data = {
            "name": subject,
            "branch": branch,
            "semester": semester,
            "content": content
        }
        return jsonify(notes_data)
    
    return jsonify({"error": "Notes not found"}), 404

@app.route('/download/<branch>/<int:semester>/<subject>', methods=['GET'])
def download_notes(branch, semester, subject):
    file_path = os.path.join(NOTES_BASE_DIR, branch, f"Semester{semester}", f"{subject}.txt")
    
    if os.path.exists(file_path):
        return send_file(file_path, as_attachment=True, download_name=f"{subject}_Notes.txt")
    
    return jsonify({"error": "File not found"}), 404

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        "status": "healthy",
        "branches": len(branches)
    })

if __name__ == '__main__':
    print("\n" + "="*60)
    print("📚 VTU NOTES MODEL - RUNNING")
    print("="*60)
    print(f"✅ Branches: {len(branches)}")
    print(f"✅ Notes directory: {NOTES_BASE_DIR}")
    print("="*60)
    print("🚀 Server running at: http://localhost:5000")
    print("="*60)
    
    app.run(host='0.0.0.0', port=5000, debug=True)