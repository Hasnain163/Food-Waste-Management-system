const menuIcon = document.getElementById("bar-icon");
const menu = document.getElementById("menu");

menuIcon?.addEventListener("click",()=> {
    if(menu.className == "hidden"){
        menu.classList.remove("hidden");
    }
    else if(menu.className != "hidden"){
        menu.classList.add("hidden");
    }
});