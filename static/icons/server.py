from flask import Flask, request, jsonify
import pdfplumber

app = Flask(__name__)

def simplify_text(text):

    # simple example
    sentences = text.split(".")
    simple = ""

    for s in sentences[:10]:
        simple += "- " + s.strip() + "<br><br>"

    return simple


@app.route("/upload", methods=["POST"])
def upload():

    pdf = request.files["pdf"]

    text = ""

    with pdfplumber.open(pdf) as pdf_file:
        for page in pdf_file.pages:
            text += page.extract_text()

    simplified = simplify_text(text)

    return jsonify({"text": simplified})


app.run(debug=True)