<!--Copyright by Juliusz Sagan. The right to copy is strictly prohibited! It is forbidden to use resources inappropriately!-->
<?php header('Content-type: text/html; charset=UTF-8'); ?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimON</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --text-color: #ffffff;
            --background-color: #1a1a2e;
            --button-hover-color: #8e2de2;
            --split-bg-color: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, var(--background-color), #16213e);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background 0.5s ease;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 600px;
            width: 90%;
        }

        #display {
            font-size: 5rem;
            font-weight: 300;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s ease;
        }

        .button-container {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        button {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1rem 2rem;
            cursor: pointer;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color);
            border: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        button:active {
            transform: translateY(1px);
        }

        #splitTimes {
            flex-grow: 1;
            overflow-y: auto;
            width: 100%;
            padding: 1rem;
            box-sizing: border-box;
            max-height: 300px;
            scrollbar-width: thin;
            scrollbar-color: var(--secondary-color) var(--background-color);
        }

        #splitTimes::-webkit-scrollbar {
            width: 8px;
        }

        #splitTimes::-webkit-scrollbar-track {
            background: var(--background-color);
        }

        #splitTimes::-webkit-scrollbar-thumb {
            background-color: var(--secondary-color);
            border-radius: 20px;
        }

        .split-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: var(--split-bg-color);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .split-item:hover {
            transform: translateX(5px);
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        }

        .edit-icon {
            cursor: pointer;
            margin-left: 1rem;
            opacity: 0.7;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .edit-icon:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .edit-input {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            margin-left: 1rem;
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--text-color);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .edit-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--secondary-color);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .running #display {
            animation: pulse 2s infinite;
        }

        @media (max-width: 600px) {
            .container {
                padding: 2rem;
            }

            #display {
                font-size: 3.5rem;
            }

            .button-container {
                flex-direction: column;
                align-items: stretch;
            }

            button {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="display">00:00:00.00</div>
        <div class="button-container">
            <button id="startStop">Start</button>
            <button id="split">Międzyczas</button>
            <button id="reset">Reset</button>
        </div>
    </div>
    <div id="splitTimes"></div>

    <script>
        const display = document.getElementById('display');
        const startStopBtn = document.getElementById('startStop');
        const splitBtn = document.getElementById('split');
        const resetBtn = document.getElementById('reset');
        const splitTimesDiv = document.getElementById('splitTimes');
        const container = document.querySelector('.container');

        let timerData = {
            isRunning: false,
            startTime: 0,
            elapsedTime: 0,
            splits: []
        };

        function updateTimerData() {
            fetch('timer.php')
                .then(response => response.json())
                .then(data => {
                    timerData = data;
                    updateDisplay();
                    updateSplits();
                    updateButtons();
                    updateBackground();
                });
        }

        function sendAction(action, additionalData = {}) {
            fetch('timer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: action, ...additionalData }),
            }).then(() => updateTimerData());
        }

        function updateDisplay() {
            let currentTime = timerData.elapsedTime;
            if (timerData.isRunning) {
                currentTime += Date.now() - timerData.startTime;
            }
            display.textContent = formatTime(currentTime);
        }

        function formatTime(time) {
            const date = new Date(time);
            return date.toISOString().substr(11, 11);
        }

        function updateSplits() {
            splitTimesDiv.innerHTML = '';
            timerData.splits.forEach((split, index) => {
                const splitElement = document.createElement('div');
                splitElement.className = 'split-item';
                splitElement.innerHTML = `
                    <span>Międzyczas ${split.count}: ${formatTime(split.time)} ${split.name ? '- ' + split.name : ''}</span>
                    <img src="https://img.icons8.com/material-outlined/24/ffffff/edit--v1.png" class="edit-icon" alt="Edytuj" title="Edytuj nazwę" onclick="editSplitName(${index})"/>
                `;
                splitTimesDiv.appendChild(splitElement);
            });
        }

        function editSplitName(index) {
            const splitElement = splitTimesDiv.children[index];
            const currentName = timerData.splits[index].name || '';
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentName;
            input.className = 'edit-input';
            input.placeholder = 'Wpisz nazwę międzyczasu';
            
            input.onblur = function() {
                const newName = this.value.trim();
                sendAction('editSplitName', { index, newName });
                updateSplits();
            };

            input.onkeypress = function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                }
            };

            splitElement.appendChild(input);
            input.focus();
        }

        function updateButtons() {
            startStopBtn.textContent = timerData.isRunning ? 'Stop' : 'Start';
            startStopBtn.style.background = timerData.isRunning 
                ? 'linear-gradient(45deg, #e74c3c, #c0392b)' 
                : 'linear-gradient(45deg, #2ecc71, #27ae60)';
            container.classList.toggle('running', timerData.isRunning);
        }

        function updateBackground() {
            const progress = (timerData.elapsedTime % 60000) / 60000; // Zmieniamy kolor co minutę
            const hue = Math.floor(progress * 360);
            document.body.style.background = `linear-gradient(135deg, hsl(${hue}, 50%, 15%), hsl(${(hue + 60) % 360}, 50%, 25%))`;
        }

        startStopBtn.addEventListener('click', () => {
            sendAction(timerData.isRunning ? 'stop' : 'start');
        });

        splitBtn.addEventListener('click', () => {
            if (timerData.isRunning) {
                sendAction('split');
            }
        });

        resetBtn.addEventListener('click', () => {
            if (confirm('Czy na pewno chcesz zresetować stoper? Wszystkie międzyczasy zostaną usunięte.')) {
                sendAction('reset');
            }
        });

        setInterval(updateTimerData, 10);
        updateTimerData();
    </script>
</body>
</html>
<!--Copyright by Juliusz Sagan. The right to copy is strictly prohibited! It is forbidden to use resources inappropriately!-->