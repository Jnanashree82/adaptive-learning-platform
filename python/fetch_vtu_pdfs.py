import os
import requests
import json
import time
from github import Github
import urllib.request

# Configuration
NOTES_BASE_DIR = "D:/xampp/htdocs/adaptive-learning-platform/vtu_notes/"
GITHUB_TOKEN = None  # Optional: Add your GitHub token for higher rate limits

# GitHub repositories containing VTU PDF notes (from search results)
GITHUB_REPOS = [
    {
        "repo": "Anish202020/8th-Semester",
        "branch": "main",
        "subjects": ["Technical Seminar 21CS81", "Internship 21CS82"],
        "semester": 8,
        "branch_code": "CSE"
    },
    {
        "repo": "Anish202020/7th-Semester",
        "branch": "main",
        "subjects": [],  # Will fetch all PDFs
        "semester": 7,
        "branch_code": "CSE"
    },
    {
        "repo": "Anish202020/6th-Semester",
        "branch": "main",
        "subjects": [],
        "semester": 6,
        "branch_code": "CSE"
    },
    {
        "repo": "shubham8298/vtu-notes-web-page",
        "branch": "master",
        "files": [
            "firstSemCcy.html",
            "firstSemPCy.html",
            "secondSemCcy.html",
            "secondSemPCy.html"
        ],
        "semester": 1,
        "branch_code": "CSE"
    },
    {
        "repo": "Prasad-Nadager/VTU-Notes",
        "branch": "main",
        "subjects": [],
        "semester": 3,
        "branch_code": "CSE"
    },
    {
        "repo": "BhuvaneshHingal/VTU-Notes",
        "branch": "master",
        "subjects": [],
        "semester": 5,
        "branch_code": "CSE"
    }
]

# Direct PDF links from VTU resources
VTU_PDF_LINKS = [
    {
        "url": "https://vtu.ac.in/pdf/cbcs/pastexams/2022/",
        "branch": "CSE",
        "semester": 1,
        "subjects": ["Mathematics-I", "Physics", "Chemistry", "C-Programming"]
    },
    {
        "url": "https://vtu.ac.in/pdf/cbcs/pastexams/2023/",
        "branch": "CSE",
        "semester": 2,
        "subjects": ["Mathematics-II", "Data-Structures", "Electronics"]
    }
]

def create_directory(path):
    """Create directory if it doesn't exist"""
    if not os.path.exists(path):
        os.makedirs(path)
        print(f"📁 Created directory: {path}")

def download_pdf(url, filepath):
    """Download PDF file from URL"""
    try:
        response = requests.get(url, timeout=30, stream=True)
        response.raise_for_status()
        
        # Check if it's a PDF
        content_type = response.headers.get('content-type', '')
        if 'pdf' in content_type.lower() or url.lower().endswith('.pdf'):
            with open(filepath, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)
            print(f"✅ Downloaded PDF: {os.path.basename(filepath)}")
            return True
        else:
            print(f"⚠️ Not a PDF: {url}")
            return False
    except Exception as e:
        print(f"❌ Failed to download {url}: {e}")
        return False

def fetch_from_github():
    """Fetch PDF files from GitHub repositories"""
    print("\n" + "="*60)
    print("📥 FETCHING PDF NOTES FROM GITHUB")
    print("="*60)
    
    for repo_info in GITHUB_REPOS:
        repo_name = repo_info["repo"]
        branch = repo_info.get("branch", "main")
        semester = repo_info["semester"]
        branch_code = repo_info["branch_code"]
        
        print(f"\n🔍 Processing repository: {repo_name}")
        
        # Create target directory
        target_dir = os.path.join(NOTES_BASE_DIR, branch_code, f"Semester{semester}")
        create_directory(target_dir)
        
        # Get repository contents via GitHub API
        api_url = f"https://api.github.com/repos/{repo_name}/git/trees/{branch}?recursive=1"
        
        try:
            headers = {}
            if GITHUB_TOKEN:
                headers["Authorization"] = f"token {GITHUB_TOKEN}"
            
            response = requests.get(api_url, headers=headers, timeout=15)
            response.raise_for_status()
            
            data = response.json()
            
            if "tree" in data:
                pdf_count = 0
                for item in data["tree"]:
                    if item["type"] == "blob" and item["path"].lower().endswith('.pdf'):
                        filename = os.path.basename(item["path"])
                        filepath = os.path.join(target_dir, filename)
                        
                        # Don't download if already exists
                        if not os.path.exists(filepath):
                            # Download raw file
                            raw_url = f"https://raw.githubusercontent.com/{repo_name}/{branch}/{item['path']}"
                            if download_pdf(raw_url, filepath):
                                pdf_count += 1
                                time.sleep(0.5)  # Be nice to GitHub
                
                print(f"📊 Downloaded {pdf_count} PDFs from {repo_name}")
                        
        except Exception as e:
            print(f"❌ Error fetching from {repo_name}: {e}")

def create_sample_pdfs():
    """Create sample PDF links for branches that don't have any"""
    print("\n" + "="*60)
    print("📝 CREATING SAMPLE PDF LINKS")
    print("="*60)
    
    branches = ["CSE", "ECE", "ME", "CE", "ISE", "AIML", "AIDS", "CSE-CY", "CSE-DS", "CSE-IOT"]
    subjects_by_branch = {
        "CSE": {
            1: ["Mathematics-I", "Physics", "Chemistry", "C-Programming"],
            2: ["Mathematics-II", "Data-Structures", "Digital-Electronics", "OOP"],
            3: ["Discrete-Mathematics", "Data-Structures-Lab", "Computer-Organization", "Software-Engineering"],
            4: ["Algorithms", "Operating-Systems", "Microcontrollers", "Java"],
            5: ["DBMS", "Computer-Networks", "Theory-of-Computation", "Compiler-Design"],
            6: ["AI", "ML", "Web-Technologies", "Cloud-Computing"],
            7: ["Big-Data", "Deep-Learning", "IoT", "NLP"],
            8: ["Blockchain", "DevOps", "Mobile-Dev", "Project"]
        },
        "ECE": {
            1: ["Mathematics-I", "Physics", "Basic-Electronics", "C-Programming"],
            2: ["Mathematics-II", "Chemistry", "Network-Analysis", "Electronic-Devices"],
            3: ["Digital-Electronics", "Signals-Systems", "Electronic-Circuits", "Network-Theory"],
            4: ["Analog-Circuits", "Control-Systems", "Communication-Theory", "Linear-ICs"],
            5: ["Digital-Communication", "DSP", "VLSI-Design", "Microwave"],
            6: ["Wireless-Comm", "Optical-Comm", "Embedded-Systems", "Computer-Networks"],
            7: ["Satellite-Comm", "RF-Engineering", "Image-Processing", "Speech-Processing"],
            8: ["5G-Comm", "AI-in-Comm", "IoT", "Project"]
        }
    }
    
    # Create HTML files that link to PDFs
    for branch in branches:
        if branch in subjects_by_branch:
            subjects = subjects_by_branch[branch]
        else:
            # Create generic subjects for other branches
            subjects = {}
            for sem in range(1, 9):
                subjects[sem] = [f"Subject-{i}-Semester-{sem}" for i in range(1, 5)]
        
        for sem in range(1, 9):
            if sem in subjects:
                dir_path = os.path.join(NOTES_BASE_DIR, branch, f"Semester{sem}")
                create_directory(dir_path)
                
                # Create an index.html file with PDF links
                index_path = os.path.join(dir_path, "index.html")
                with open(index_path, 'w', encoding='utf-8') as f:
                    f.write(f"""<!DOCTYPE html>
<html>
<head>
    <title>{branch} - Semester {sem} Notes</title>
    <style>
        body {{ font-family: Arial; padding: 20px; background: #f5f5f5; }}
        .container {{ max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }}
        h1 {{ color: #333; }}
        .pdf-list {{ list-style: none; padding: 0; }}
        .pdf-item {{ margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; border-radius: 5px; }}
        .pdf-link {{ color: #007bff; text-decoration: none; font-size: 16px; }}
        .pdf-link:hover {{ text-decoration: underline; }}
        .note {{ color: #666; font-size: 14px; margin-top: 5px; }}
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 {branch} - Semester {sem} Notes (PDF)</h1>
        <p>Click on the links below to download PDF notes</p>
        <ul class="pdf-list">
""")
                    
                    for subject in subjects[sem]:
                        f.write(f"""
            <li class="pdf-item">
                <a class="pdf-link" href="https://vtu.ac.in/pdf/cbcs/{subject}.pdf" target="_blank">
                    📄 {subject.replace('-', ' ')} Notes
                </a>
                <div class="note">PDF format - Click to download</div>
            </li>
""")
                    
                    f.write("""
        </ul>
        <p style="margin-top: 30px; color: #999; font-size: 12px;">
            Notes sourced from VTU official resources and GitHub repositories
        </p>
    </div>
</body>
</html>
""")
                print(f"📄 Created index page: {index_path}")

def download_from_vtu_sources():
    """Attempt to download actual PDFs from VTU sources"""
    print("\n" + "="*60)
    print("📥 DOWNLOADING FROM VTU SOURCES")
    print("="*60)
    
    for link_info in VTU_PDF_LINKS:
        branch = link_info["branch"]
        semester = link_info["semester"]
        subjects = link_info["subjects"]
        
        dir_path = os.path.join(NOTES_BASE_DIR, branch, f"Semester{semester}")
        create_directory(dir_path)
        
        for subject in subjects:
            # Try different PDF naming patterns
            patterns = [
                f"{subject}.pdf",
                f"{subject.lower()}.pdf",
                f"{subject.replace('-', '')}.pdf",
                f"VTU_{branch}_Sem{semester}_{subject}.pdf"
            ]
            
            for pattern in patterns:
                pdf_url = f"https://vtu.ac.in/pdf/cbcs/{pattern}"
                filepath = os.path.join(dir_path, f"{subject}.pdf")
                
                if not os.path.exists(filepath):
                    if download_pdf(pdf_url, filepath):
                        break
                    time.sleep(1)

def update_notes_from_circulars():
    """Main function to fetch and update all notes"""
    print("\n" + "="*60)
    print("📚 VTU PDF NOTES FETCHER - AUTOMATIC DOWNLOAD")
    print("="*60)
    
    # Create base directory
    create_directory(NOTES_BASE_DIR)
    
    # Fetch from GitHub
    fetch_from_github()
    
    # Download from VTU sources
    download_from_vtu_sources()
    
    # Create sample PDF links
    create_sample_pdfs()
    
    print("\n" + "="*60)
    print("✅ PDF NOTES FETCHING COMPLETED")
    print("="*60)
    print(f"📁 Notes saved in: {NOTES_BASE_DIR}")
    print("="*60)

if __name__ == "__main__":
    update_notes_from_circulars()