import os
import requests
import json
import time
from github import Github
from bs4 import BeautifulSoup
import urllib.request

# Configuration
NOTES_BASE_DIR = "D:/xampp/htdocs/adaptive-learning-platform/vtu_notes/"
GITHUB_TOKEN = None  # Optional: Add your GitHub token for higher rate limits

# GitHub repositories containing VTU notes (from search results)
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
        "subjects": [],  # Will fetch all subjects
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
    }
]

def create_directory(path):
    """Create directory if it doesn't exist"""
    if not os.path.exists(path):
        os.makedirs(path)
        print(f"📁 Created directory: {path}")

def download_file(url, filepath):
    """Download file from URL"""
    try:
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        with open(filepath, 'wb') as f:
            f.write(response.content)
        print(f"✅ Downloaded: {filepath}")
        return True
    except Exception as e:
        print(f"❌ Failed to download {url}: {e}")
        return False

def fetch_from_github():
    """Fetch notes from GitHub repositories"""
    print("\n" + "="*60)
    print("📥 FETCHING NOTES FROM GITHUB")
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
                for item in data["tree"]:
                    if item["type"] == "blob" and item["path"].endswith(('.pdf', '.md', '.txt', '.docx', '.html')):
                        filename = os.path.basename(item["path"])
                        filepath = os.path.join(target_dir, filename)
                        
                        # Download raw file
                        raw_url = f"https://raw.githubusercontent.com/{repo_name}/{branch}/{item['path']}"
                        download_file(raw_url, filepath)
                        time.sleep(0.5)  # Be nice to GitHub
                        
        except Exception as e:
            print(f"❌ Error fetching from {repo_name}: {e}")

def extract_notes_from_html():
    """Extract notes from HTML files in vtu-notes-web-page"""
    html_dir = os.path.join(NOTES_BASE_DIR, "CSE", "Semester1")
    
    for repo_info in GITHUB_REPOS:
        if "files" in repo_info:
            for html_file in repo_info["files"]:
                filepath = os.path.join(html_dir, html_file.replace('.html', '.txt'))
                
                # Parse HTML content if it exists
                html_path = os.path.join(html_dir, html_file)
                if os.path.exists(html_path):
                    with open(html_path, 'r', encoding='utf-8') as f:
                        content = f.read()
                    
                    # Extract text from HTML
                    soup = BeautifulSoup(content, 'html.parser')
                    text = soup.get_text()
                    
                    # Save as text file
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(text)
                    print(f"✅ Extracted text from {html_file}")

def create_sample_notes():
    """Create sample notes for branches that don't have any"""
    print("\n" + "="*60)
    print("📝 CREATING SAMPLE NOTES")
    print("="*60)
    
    branches = ["CSE", "ECE", "ME", "CE", "ISE", "AIML", "AIDS"]
    subjects_by_branch = {
        "CSE": {
            1: ["Engineering Mathematics I", "Engineering Physics", "C Programming", "Basic Electrical"],
            2: ["Engineering Mathematics II", "Engineering Chemistry", "Data Structures", "Basic Electronics"],
            3: ["Data Structures", "Digital Electronics", "Computer Organization", "Software Engineering"],
            4: ["Algorithms", "Operating Systems", "Microcontrollers", "Java Programming"],
            5: ["DBMS", "Computer Networks", "Theory of Computation", "Compiler Design"],
            6: ["Artificial Intelligence", "Machine Learning", "Web Technologies", "Cloud Computing"],
            7: ["Big Data", "Deep Learning", "IoT", "NLP"],
            8: ["Blockchain", "DevOps", "Mobile App Dev", "Project Work"]
        },
        "ECE": {
            1: ["Mathematics I", "Physics", "Basic Electronics", "C Programming"],
            2: ["Mathematics II", "Chemistry", "Network Analysis", "Electronic Devices"],
            3: ["Digital Electronics", "Signals & Systems", "Electronic Circuits", "Network Theory"],
            4: ["Analog Circuits", "Control Systems", "Communication Theory", "Linear ICs"],
            5: ["Digital Communication", "DSP", "VLSI Design", "Microwave"],
            6: ["Wireless Comm", "Optical Comm", "Embedded Systems", "Computer Networks"],
            7: ["Satellite Comm", "RF Engineering", "Image Processing", "Speech Processing"],
            8: ["5G Comm", "AI in Comm", "IoT", "Project"]
        }
    }
    
    for branch in branches:
        if branch in subjects_by_branch:
            subjects = subjects_by_branch[branch]
        else:
            # Create generic subjects for other branches
            subjects = {}
            for sem in range(1, 9):
                subjects[sem] = [f"Subject {i} Semester {sem}" for i in range(1, 5)]
        
        for sem in range(1, 9):
            if sem in subjects:
                dir_path = os.path.join(NOTES_BASE_DIR, branch, f"Semester{sem}")
                create_directory(dir_path)
                
                for subject in subjects[sem]:
                    filename = f"{subject.replace(' ', '_')}.txt"
                    filepath = os.path.join(dir_path, filename)
                    
                    if not os.path.exists(filepath):
                        with open(filepath, 'w', encoding='utf-8') as f:
                            f.write(f"""VTU NOTES - {subject}
================================
Branch: {branch}
Semester: {sem}
Subject: {subject}

MODULE 1: INTRODUCTION
======================
• Topic 1: Basic concepts
• Topic 2: Fundamental principles
• Topic 3: Key definitions

MODULE 2: CORE CONCEPTS
=======================
• Important theories
• Practical applications
• Solved examples

MODULE 3: ADVANCED TOPICS
=========================
• Advanced concepts
• Real-world applications
• Case studies

MODULE 4: NUMERICAL PROBLEMS
============================
• Problem 1 with solution
• Problem 2 with solution
• Problem 3 with solution

MODULE 5: PREVIOUS YEAR QUESTIONS
================================
1. Question 1 from 2023 exam
2. Question 2 from 2022 exam
3. Question 3 from 2021 exam

VTU SYLLABUS REFERENCE
======================
As per VTU circular dated: March 2025

---
Notes automatically generated for VTU students
Source: Based on VTU curriculum and circulars
""")
                        print(f"📝 Created: {subject}")

def update_notes_from_circulars():
    """Main function to fetch and update all notes"""
    print("\n" + "="*60)
    print("📚 VTU NOTES FETCHER - AUTOMATIC DOWNLOAD")
    print("="*60)
    
    # Create base directory
    create_directory(NOTES_BASE_DIR)
    
    # Fetch from GitHub
    fetch_from_github()
    
    # Extract from HTML
    extract_notes_from_html()
    
    # Create sample notes for missing subjects
    create_sample_notes()
    
    print("\n" + "="*60)
    print("✅ NOTES FETCHING COMPLETED")
    print("="*60)
    print(f"📁 Notes saved in: {NOTES_BASE_DIR}")
    print("="*60)

if __name__ == "__main__":
    update_notes_from_circulars()