
const questions = [
    { title: "What will you use this PC for?",
        options: ["Gaming", "Work/Office", "Creative Work", "Programming"]
    },
    { title: "What types of programs or games will you run?",
        options: ["Web browsing & office apps", "Photo/video editing & design", "Programming & development tools", "High-performance gaming", "Streaming & content creation"]
    },
    { title: "How important to you is the possibility of a future upgrade of your PC?",
        options: ["Not important", "Somewhat important", "Very important"]
    },
    {
        title: "How much storage space do you need?",
        options: [
            "Just for basic tasks (web browsing, documents, light apps)",
            "Enough for work and casual use (documents, some games, photos)",
            "Plenty of space for large files (videos, games, creative projects)",
            "I store a massive amount of data (large media collections, professional work)"
        ]
    },
    { title: "Do you have brand preferences?",
        options: ["Intel + NVIDIA", "AMD", "No Preference"] }
];

let currentStep = 0;
let userAnswers = {};

document.getElementById("total-steps").textContent = questions.length;
const stepNumber = document.getElementById("step-number");
const progressBar = document.getElementById("progress-bar");
const questionTitle = document.getElementById("question-title");
const optionsContainer = document.getElementById("options");
const prevBtn = document.getElementById("prev-btn");
const nextBtn = document.getElementById("next-btn");

document.getElementById("start-config").addEventListener("click", function() {
    document.getElementById("main-content").classList.add("hidden");
    document.getElementById("questionnaire").classList.remove("hidden");
});

function renderStep() {
    const step = questions[currentStep];
    questionTitle.textContent = step.title;
    optionsContainer.innerHTML = step.options.map(option =>
        `<button class="w-full flex justify-between px-4 py-4 border border-gray-300 text-gray-700 bg-white rounded-md hover:bg-blue-50"
                onclick="selectOption('${step.title}','${option}')">
                <span>${option}</span>
            </button>`
    ).join("");

    stepNumber.textContent = currentStep + 1;
    progressBar.style.width = `${((currentStep + 1) / questions.length) * 100}%`;

    prevBtn.style.display = currentStep === 0 ? "none" : "block";
    nextBtn.style.display = currentStep === questions.length - 1 ? "none" : "block";
}

function selectOption(title, option) {

    userAnswers[title] = option;
    console.log(JSON.stringify(userAnswers));
    nextStep();
}

function nextStep() {
    if (currentStep < questions.length - 1) {
        currentStep++;
        renderStep();
    } else {
        sendAnswersToBackend();
    }
}

function prevStep() {
    if (currentStep > 0) {
        currentStep--;
        renderStep();
    }
}

function sendAnswersToBackend() {
    //console.log(userAnswers)
    fetch("/configurator/ai", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(userAnswers)
    })
        .then(response => {

            if (response.ok) {
                // Redirect to PC build configuration page
                window.location.href = '/configurator/build';
            } else {
                console.error('Error processing request');
            }
        })
        /*.then(data => {
            questionnaire.innerHTML = `
                <h2 class="text-xl font-bold mb-4">Recommended PC Configuration</h2>
                <p>${data.recommendation}</p>
            `;
        })*/
        .catch(error => console.error("Error:", error));
}

prevBtn.addEventListener("click", prevStep);
nextBtn.addEventListener("click", nextStep);

renderStep();