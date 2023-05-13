// Yes, jQuery syntax is used here. It's just for demonstration purposes 'cause why now?
const $ = document.querySelector.bind(document);

function handleClick() {
    const counter = $('#counter');
    counter.textContent = parseInt(counter.textContent) + 1;
}