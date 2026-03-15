let simplifiedText = []

async function uploadPDF(){

let file = document.getElementById("pdfFile").files[0]

let formData = new FormData()
formData.append("pdf", file)

let response = await fetch("/upload",{
method:"POST",
body:formData
})

let data = await response.json()

simplifiedText = data.text

let reader = document.getElementById("reader")

let html = "<ul>"

data.text.forEach(line => {

html += `<li><strong>${line}</strong></li>`

})

html += "</ul>"

reader.innerHTML = html

}



async function readText(){

let response = await fetch("/tts",{

method:"POST",
headers:{
"Content-Type":"application/json"
},
body:JSON.stringify({
text:simplifiedText
})

})

let data = await response.json()

let audio = document.getElementById("audioPlayer")
audio.src = data.audio
audio.play()

}



function toggleFocus(){

let reader = document.getElementById("reader")

reader.classList.toggle("focus")

}

function toggleFocus() {
    document.body.classList.toggle("focus-mode");
}