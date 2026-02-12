
function setActiveWindow(){
    let activeScript = location.pathname.split("/").slice(-1)[0];
    let activeItem = document.querySelectorAll("a[href='"+activeScript+"']")[0];
        activeItem.classList.add('active'); 
}

window.onload = function(){
    setActiveWindow();
}