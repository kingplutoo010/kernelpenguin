<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sticky Notes</title>
  <style>
    :root {
      --note-width: 250px;
      --note-height: 250px;
      --popup-bg: #1e1e2f;
      --text-color: #ffffff;
      --input-bg: #2c2c3a;
      --border-color: #444;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #1e1e2f, #282c34);
      margin: 0;
      padding: 0;
      color: var(--text-color);
    }

    .container {
      text-align: center;
      padding: 1rem 2rem 2rem 2rem;
    }

    h1 {
      color: #fff;
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }

    .notes-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 1.5rem;
      padding: 0.3rem 1rem 1rem 1rem;
    }

    .note {
      width: var(--note-width);
      height: var(--note-height);
      background-color: var(--note-color, #fef68a);
      padding: 0.8rem;
      border-radius: 1rem;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      transform: rotate(var(--rotate-angle, 0deg));
      cursor: pointer;
      position: relative;
      transition: transform 0.2s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }

    .note:hover {
      transform: scale(1.05) rotate(var(--rotate-angle, 0deg));
      box-shadow: 0 8px 16px rgba(0,0,0,0.4);
    }

    .note h3 {
      margin: 0 0 0.2rem;
      font-size: 0.9rem;
      color: #222;
      text-align: left;
    }

    .note p {
      font-size: 0.95rem;
      color: #222;
      line-height: 1.4;
      text-align: left;
      max-height: 180px;
      overflow-y: auto;
    }

    .delete-icon {
      position: absolute;
      top: 0.5rem;
      right: 0.7rem;
      background: transparent;
      border: none;
      font-size: 1.3rem;
      color: #f55;
      cursor: pointer;
    }

    .add-note-btn {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(45deg, #00c9ff, #92fe9d);
      border: none;
      font-size: 2rem;
      color: white;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      transition: transform 0.3s ease;
    }

    .add-note-btn:hover {
      transform: scale(1.15);
    }

    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal-content {
      background: var(--popup-bg);
      padding: 3rem;
      border-radius: 1.5rem;
      width: 80%;
      max-width: 800px;
      height: 70vh;
      box-shadow: none;
      display: flex;
      flex-direction: column;
      gap: 2rem;
      color: var(--text-color);
      overflow: auto;
    }

    .modal-content input,
    .modal-content textarea {
      background: var(--input-bg);
      color: var(--text-color);
      width: 100%;
      padding: 1rem;
      font-size: 1rem;
      border-radius: none;
      border: none;
      outline: none;
    }

    .modal-content textarea {
  background: var(--input-bg);
  color: var(--text-color);
  width: 100%;
  height: 40vh;              /* More vertical space */
  padding: 1rem;
  font-size: 1.1rem;
  /*border-radius: none;
 /* border: none;              /* 🔴 This removes the border */
  outline: none;
  resize: none;              /* Optional: disables manual resizing */
}

    @media (max-width: 600px) {
      .note {
        width: 90vw;
        height: auto;
        min-height: 200px;
      }

      .modal-content {
        padding: 1.5rem;
        max-width: 95vw;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Sticky Notes</h1>
    <div class="notes-container" id="notesContainer"></div>
  </div>

  <button class="add-note-btn" id="addNoteBtn" title="Add Note">+</button>

  <script>
    const notesContainer = document.getElementById('notesContainer');
    const addNoteBtn = document.getElementById('addNoteBtn');

    const defaultColors = [
      "#fef68a", "#f4cccc", "#d9ead3", "#c9daf8", "#f9cb9c",
      "#ead1dc", "#d0e0e3", "#fff2cc", "#d9d2e9", "#ffe599"
    ];

    function createNote(title, content, color, id = null) {
      const noteEl = document.createElement('div');
      noteEl.className = 'note';
      noteEl.style.setProperty('--note-color', color);
      noteEl.style.setProperty('--rotate-angle', ((Math.random() * 6) - 3).toFixed(2));
      if (id) noteEl.dataset.noteId = id;

      noteEl.innerHTML = `
        <h3>${title}</h3>
        <p>${content}</p>
      `;

      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'delete-icon';
      deleteBtn.innerHTML = '&times;';
      noteEl.appendChild(deleteBtn);

      deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!noteEl.dataset.noteId) {
          noteEl.style.transition = 'opacity 0.4s ease';
          noteEl.style.opacity = '0';
          setTimeout(() => noteEl.remove(), 400);
          return;
        }

        fetch('delete_note.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ id: noteEl.dataset.noteId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            noteEl.style.transition = 'opacity 0.4s ease';
            noteEl.style.opacity = '0';
            setTimeout(() => noteEl.remove(), 400);
          } else {
            alert('Failed to delete note');
          }
        });
      });

      noteEl.addEventListener('click', () => {
        const currentTitle = noteEl.querySelector('h3').textContent;
        const currentContent = noteEl.querySelector('p').textContent;

        const modal = document.createElement('div');
        modal.className = 'modal';

        const editor = document.createElement('div');
        editor.className = 'modal-content';
        editor.innerHTML = `
          <input value="${currentTitle}" />
          <textarea>${currentContent}</textarea>
        `;

        modal.appendChild(editor);
        document.body.appendChild(modal);

        const [titleInput, contentArea] = editor.children;

        const saveNote = () => {
          const newTitle = titleInput.value.trim();
          const newContent = contentArea.value.trim();

          fetch('save_note.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
              id: noteEl.dataset.noteId || null,
              title: newTitle,
              content: newContent,
              color: color
            })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              noteEl.querySelector('h3').textContent = newTitle;
              noteEl.querySelector('p').textContent = newContent;
              if (!noteEl.dataset.noteId && data.id) {
                noteEl.dataset.noteId = data.id;
              }
            } else {
              alert("Failed to save note");
            }
          });

          modal.remove();
        };

        modal.addEventListener('click', (e) => {
          if (e.target === modal) {
            saveNote();
          }
        });
      });

      notesContainer.appendChild(noteEl);
    }

    function loadNotesFromServer() {
      fetch('get_notes.php', {
        method: 'GET',
        credentials: 'include'
      })
      .then(res => res.json())
      .then(data => {
        if (Array.isArray(data)) {
          data.forEach(note => {
            createNote(note.title, note.content, note.color, note.id);
          });
        }
      })
      .catch(err => {
        console.error("Error loading notes:", err);
      });
    }

    addNoteBtn.addEventListener('click', () => {
      const title = "New Note";
      const content = "";
      const color = defaultColors[Math.floor(Math.random() * defaultColors.length)];

      fetch('save_note.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ title, content, color })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          createNote(title, content, color, data.id);
        } else {
          alert('Failed to create note');
        }
      });
    });

    loadNotesFromServer();
  </script>
</body>
</html>
