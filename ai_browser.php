<?php
// ai_browser.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini AI Browser</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" integrity="sha512-fD9DI5bZwQxOi7MhYWnnNPlvXdp/2Pj3XSTRrFs5FQa4mizyGLnJcN6tuvUS6Sfmg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        :root {
            --primary: #1e90ff;
            --accent: #ff6b6b;
            --bg-start: #0a0a0a;
            --bg-end: #1c2526;
            --card-bg: rgba(255, 255, 255, 0.05);
            --shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            --text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-start), var(--bg-end));
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }

        .ai-browser-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 30px;
            width: 90%;
            max-width: 1000px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin: 20px 0;
            position: relative;
            z-index: 2;
        }

        .response-textbox {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            font-size: 1.1rem;
            font-weight: 300;
            color: #ffffff;
            resize: none;
            min-height: 300px;
            max-height: 60vh;
            width: 100%;
            overflow-y: auto;
            transition: border-color 0.3s ease;
        }

        .response-textbox:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(30, 144, 255, 0.3);
        }

        .input-area {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .query-textbox {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            font-size: 1.2rem;
            font-weight: 400;
            color: #ffffff;
            resize: none;
            min-height: 100px;
            width: 100%;
            transition: border-color 0.3s ease;
        }

        .query-textbox:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(30, 144, 255, 0.3);
        }

        .submit-button {
            background: linear-gradient(45deg, var(--primary), #00b7eb);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            text-shadow: var(--text-shadow);
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 144, 255, 0.4);
        }

        .submit-button:active {
            transform: scale(0.95);
        }

        .submit-button .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .error-message {
            display: none;
            color: var(--accent);
            font-size: 0.9rem;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) scale(0.5); }
            100% { transform: translateY(-10vh) scale(1); }
        }

        @media (max-width: 768px) {
            .ai-browser-container {
                padding: 20px;
            }
            .query-textbox, .response-textbox {
                font-size: 1rem;
            }
            .submit-button {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            .query-textbox {
                min-height: 80px;
            }
        }

        .query-textbox:focus, .response-textbox:focus, .submit-button:focus {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div id="particles"></div>
    <div class="ai-browser-container">
        <textarea class="response-textbox" id="responseTextbox" readonly aria-label="Query response display" placeholder="Responses will appear here..."></textarea>
        <div class="error-message" id="errorMessage"></div>
        <div class="input-area">
            <textarea class="query-textbox" id="queryTextbox" placeholder="Type your query..." aria-label="Enter your query" maxlength="500"></textarea>
            <button class="submit-button" id="submitButton" aria-label="Submit query">
                <i class="fas fa-paper-plane"></i> Submit
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const queryTextbox = document.getElementById('queryTextbox');
            const submitButton = document.getElementById('submitButton');
            const responseTextbox = document.getElementById('responseTextbox');
            const errorMessage = document.getElementById('errorMessage');

            // Auto-resize query textarea
            queryTextbox.addEventListener('input', () => {
                queryTextbox.style.height = 'auto';
                queryTextbox.style.height = `${queryTextbox.scrollHeight}px`;
            });

            // Ripple effect for submit button
            submitButton.addEventListener('click', async (e) => {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                const rect = submitButton.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = `${size}px`;
                ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
                ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
                submitButton.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);

                // Handle query submission
                const query = queryTextbox.value.trim();
                if (!query) {
                    errorMessage.textContent = 'Please enter a query.';
                    errorMessage.style.display = 'block';
                    setTimeout(() => errorMessage.style.display = 'none', 5000);
                    return;
                }

                try {
                    responseTextbox.value = 'Processing...';
                    const response = await fetch('http://localhost:3000/api/gemini', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ query })
                    });
                    const data = await response.json();
                    responseTextbox.value = data.success ? data.response : `Error: ${data.error || 'Failed to get response from Gemini API.'}`;
                } catch (error) {
                    responseTextbox.value = 'Failed to connect to the server. Please try again.';
                    errorMessage.textContent = 'Network error. Check if the server is running.';
                    errorMessage.style.display = 'block';
                    setTimeout(() => errorMessage.style.display = 'none', 5000);
                }

                queryTextbox.value = '';
                queryTextbox.style.height = 'auto';
                queryTextbox.focus();
            });

            // Submit on Enter key (no Shift)
            let debounceTimeout;
            queryTextbox.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(() => submitButton.click(), 300);
                }
            });

            // Generate Particles
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                particle.style.width = `${Math.random() * 4 + 2}px`;
                particle.style.height = particle.style.width;
                particle.style.left = `${Math.random() * 100}vw`;
                particle.style.animationDelay = `${Math.random() * 10}s`;
                particle.style.animationDuration = `${Math.random() * 10 + 10}s`;
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>
