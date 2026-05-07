import "./bootstrap";
import Quill from "quill";

document.addEventListener("DOMContentLoaded", () => {
    try {
        const editorElement = document.querySelector("#editor");
        if (editorElement) {
            const quill = new Quill("#editor", {
                theme: "snow",
                modules: {
                    toolbar: [
                        ["bold", "italic", "underline", "strike"],
                        ["link", { list: "ordered" }, { list: "bullet" }],
                        [{ align: [] }],
                        ["clean"],
                    ],
                },
                placeholder: "Write your announcement here...",
            });
            // Sync editor content with textarea
            quill.on("text-change", () => {
                document.getElementById("content").value = quill.root.innerHTML;
            });
            // Set initial value
            document.getElementById("content").value = quill.root.innerHTML;
            console.log("Quill initialized successfully.");
        }
    } catch (error) {
        console.error("Quill initialization failed:", error);
    }
});


