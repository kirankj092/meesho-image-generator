// ============================================================
// MEESHO IMAGE GENERATOR — script.js
// ============================================================

const dropzone     = document.getElementById('dropzone');
const fileInput    = document.getElementById('fileInput');
const dropzoneIdle = document.getElementById('dropzoneIdle');
const dropzonePreview = document.getElementById('dropzonePreview');
const previewImg   = document.getElementById('previewImg');
const removeBtn    = document.getElementById('removeBtn');
const generateBtn  = document.getElementById('generateBtn');
const weightInput  = document.getElementById('weightInput');

// ---- Click to browse ----
dropzone.addEventListener('click', () => {
    if (!dropzonePreview.style.display || dropzonePreview.style.display === 'none') {
        fileInput.click();
    }
});

// ---- Drag and drop ----
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
});

// ---- File input change ----
fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) handleFile(fileInput.files[0]);
});

// ---- Handle file ----
function handleFile(file) {
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
        alert('Only JPG, PNG and WebP files are allowed.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be under 5MB.');
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImg.src = e.target.result;
        dropzoneIdle.style.display = 'none';
        dropzonePreview.style.display = 'block';
        generateBtn.disabled = false;
    };
    reader.readAsDataURL(file);
}

// ---- Remove image ----
removeBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    previewImg.src = '';
    fileInput.value = '';
    dropzoneIdle.style.display = 'block';
    dropzonePreview.style.display = 'none';
    generateBtn.disabled = true;
});


// ---- Generate button loading state ----
document.getElementById('uploadForm').addEventListener('submit', () => {
    generateBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Generating... Please wait</span>';
    generateBtn.disabled = true;
});