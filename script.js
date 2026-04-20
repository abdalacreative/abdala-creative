const taskInput = document.getElementById("taskInput");
const addBtn = document.getElementById("addBtn");
const taskList = document.getElementById("taskList");
const clearBtn = document.getElementById("clearBtn");
const searchInput = document.getElementById("searchInput");


addBtn.onclick = function () {
    if (taskInput.value === "") return;

    let li = document.createElement("li");

    li.innerHTML = `
        <span class="task-text">${taskInput.value}</span>
        
        <div class="actions">
            <!-- ICON DELETE HALKAN KU DAR -->
            <span class="delete">🛑</span>
            
            <!-- ICON COMPLETE HALKAN KU DAR -->
            <span class="complete">✅</span>
        </div>
    `;

    taskList.appendChild(li);
    taskInput.value = "";
};


taskList.addEventListener("click", function (e) {
    if (e.target.classList.contains("delete")) {
        e.target.parentElement.parentElement.remove();
    }

    if (e.target.classList.contains("complete")) {
        e.target.parentElement.previousElementSibling.classList.toggle("completed");
    }
});


clearBtn.onclick = function () {
    taskList.innerHTML = "";
};


searchInput.addEventListener("keyup", function () {
    let filter = searchInput.value.toLowerCase();
    let tasks = document.querySelectorAll("li");

    tasks.forEach(task => {
        let text = task.querySelector(".task-text").textContent.toLowerCase();
        task.style.display = text.includes(filter) ? "flex" : "none";
    });
});