from flask import Flask, render_template, request, jsonify
import pdfplumber
from gtts import gTTS
import os

app = Flask(__name__)

UPLOAD_FOLDER = "uploads"
app.config["UPLOAD_FOLDER"] = UPLOAD_FOLDER


def simplify_text(text):

    sentences = text.split(".")
    simple = []

    for s in sentences[:15]:
        if len(s.strip()) > 0:
            simple.append(s.strip())

    return simple


@app.route("/")
def home():
    return render_template("index.html")


@app.route("/upload", methods=["POST"])
def upload_pdf():

    file = request.files["pdf"]

    filepath = os.path.join(app.config["UPLOAD_FOLDER"], file.filename)
    file.save(filepath)

    text = ""

    with pdfplumber.open(filepath) as pdf:
        for page in pdf.pages:
            if page.extract_text():
                text += page.extract_text()

    simplified = simplify_text(text)

    return jsonify({"text": simplified})


@app.route("/tts", methods=["POST"])
def text_to_speech():

    data = request.json
    text = " ".join(data["text"])

    tts = gTTS(text)
    audio_file = "static/audio.mp3"
    tts.save(audio_file)

    return jsonify({"audio": audio_file})


if __name__ == "__main__":
    app.run(debug=True)