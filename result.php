<?php
session_start();

// Redirect to index if user is not authenticated
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: index.php");
    exit(); // Stop further script execution
}

// If authenticated, get the username
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Results</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak] { display: none !important; }
    .trophy {
      animation: bounce 2s infinite;
    }
    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    .confetti {
      position: absolute;
      width: 10px;
      height: 10px;
      background-color: #f00;
      opacity: 0.7;
    }
    
    /* Animated Progress Bar */
    .progress-container {
      height: 16px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      overflow: hidden;
      position: relative;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    
    .progress-bar {
      height: 100%;
      border-radius: 10px;
      background: linear-gradient(90deg, #3b82f6, #8b5cf6);
      position: relative;
      transition: width 1s cubic-bezier(0.65, 0, 0.35, 1);
      width: 0;
    }
    
    .progress-bar::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0.3) 0%,
        rgba(255, 255, 255, 0) 50%,
        rgba(255, 255, 255, 0.3) 100%
      );
      background-size: 200% 100%;
      animation: shine 2s infinite;
      border-radius: 10px;
    }
    
    .progress-thumb {
      width: 24px;
      height: 24px;
      background: white;
      border-radius: 50%;
      position: absolute;
      right: -12px;
      top: 50%;
      transform: translateY(-50%);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3), 
                  0 0 0 3px rgba(59, 130, 246, 0.5);
      transition: right 1s cubic-bezier(0.65, 0, 0.35, 1);
    }
    
    @keyframes shine {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    
    .progress-labels {
      display: flex;
      justify-content: space-between;
      margin-top: 8px;
      font-size: 0.875rem;
      color: rgba(255, 255, 255, 0.8);
    }
    
    .percentage-display {
      position: absolute;
      right: 0;
      top: -30px;
      background: white;
      color: #3b82f6;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: bold;
      transform: translateX(50%);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .progress-thumb:hover .percentage-display {
      opacity: 1;
    }
  </style>
</head>
<body class="h-screen items-center justify-center p-4 overflow-hidden">
    <!-- 1st Video Background -->
    <video autoplay muted loop 
    id="forest"
    class="absolute top-0 left-0 w-full h-full object-cover transition-transform duration-[2000ms] translate-x-0 -z-0">
    <source src="video/username_background.mp4" type="video/mp4">
  </video>

  <!-- 2nd Video Background -->
  <video autoplay muted loop 
    id="waterfalls"
    class="absolute top-0 left-0 w-full h-full object-cover transition-transform duration-[2000ms] translate-x-full -z-10">
    <source src="video/waterfalls.mp4" type="video/mp4">
  </video>
  <script>
  window.quizData = { 
    username: <?php echo json_encode($username); ?>, 
  };
  </script>

  <div 
    x-data="{
      score: 0,
      timeBonus: 0,
      totalQuestions: 0,
      difficulty: 'medium',
      soundEnabled: true,
      showConfetti: false,
      progressWidth: 0,
      correctAnswers: 0,
      isLoading: true,
      error: null,  
      username:  window.quizData.username || 'Guest',
      
   async init() {
    try {
        const response = await axios.get('http://localhost/tailwind_quiz_game/server/routes/api.php/results');
        
        // Validate response structure
        if (!response.data || 
            !response.data.success || 
            !response.data.data || 
            !Array.isArray(response.data.data.responses)) {
            throw new Error('Invalid server response format');
        }
        const resultData = response.data.data;
        
        this.totalQuestions = resultData.totalQuestions;
        this.correctAnswers = resultData.correctAnswers;
        this.difficulty = resultData.difficulty;
        
        // Calculate base score (correct answers with difficulty multiplier)
        const baseScorePerQuestion = 100;
        const difficultyMultiplier = this.getDifficultyMultiplier(this.difficulty);
        this.score = this.correctAnswers * baseScorePerQuestion * difficultyMultiplier;
        
        // Get the final question data (where we'll find the remaining time)
        const finalResponse = resultData.responses[resultData.responses.length - 1];
        
        // Calculate time bonus based on shared timer (only if quiz was completed)
        if (finalResponse && finalResponse.timeLeft > 0) {
            // Time bonus formula: remaining seconds × difficulty multiplier × bonus factor
            const timeBonusMultiplier = 5; // Gives 5 points per remaining second
            this.timeBonus = Math.floor(finalResponse.timeLeft * difficultyMultiplier * timeBonusMultiplier);
            this.score += this.timeBonus;
        }
        
 
            try {
                await axios.post('http://localhost/tailwind_quiz_game/server/routes/api.php/results', {
                    userId: this.userId,
                    sessionId: this.sessionId,
                    score: this.score,
                    correctAnswers: this.correctAnswers,
                    totalQuestions: this.totalQuestions,
                    difficulty: this.difficulty,
                    timeBonus: this.timeBonus
                });
            } catch (saveError) {
                console.error('Error saving results:', saveError);
            }
      
        
        // Animate progress bar
        const targetWidth = (this.correctAnswers / this.totalQuestions) * 100;
        const animationDuration = 1500;
        const startTime = performance.now();
        
        const animateProgress = (timestamp) => {
            const elapsed = timestamp - startTime;
            const progress = Math.min(elapsed / animationDuration, 1);
            this.progressWidth = progress * targetWidth;
            
            if (progress < 1) {
                requestAnimationFrame(animateProgress);
            }
        };
        
        requestAnimationFrame(animateProgress);
        
        // Show confetti if accuracy is high (75% or more correct)
        if ((this.correctAnswers / this.totalQuestions) * 100 >= 75) {
            this.createConfetti();
        }
        
        this.isLoading = false; 
    } catch (error) {
        console.error('Results loading error:', error);
        this.error = error.response?.data?.message || 
                   error.message || 
                   'Failed to load results. Please try again.';
        this.isLoading = false;
    }
}, 

      getDifficultyMultiplier(difficulty) {
    switch(difficulty) {
        case 'easy': return 1;
        case 'medium': return 2;
        case 'hard': return 3;
        default: return 1;
    }
},

      get baseScorePerQuestion() {
        return 100; // Fixed base score per correct answer
      },
      
    get maxPossibleScore() {
    // Base score for all correct answers with difficulty multiplier
    const baseScore = this.totalQuestions * 100 * this.getDifficultyMultiplier(this.difficulty);
    
    // Maximum possible time bonus (calculated from total possible time)
    const timePerQuestion = {
        easy: 15,
        medium: 10,
        hard: 5
    };
    const totalTime = this.totalQuestions * timePerQuestion[this.difficulty];
    const timeBonusMultiplier = 5;
    
    return baseScore + (totalTime * this.getDifficultyMultiplier(this.difficulty) * timeBonusMultiplier);
},
      
    
      
      get performanceRating() {
        const percentage = (this.score / this.maxPossibleScore) * 100;
        if (percentage >= 90) return 'Legendary!';
        if (percentage >= 75) return 'Excellent!';
        if (percentage >= 60) return 'Good Job!';
        if (percentage >= 40) return 'Not Bad!';
        return 'Keep Practicing!';
      },
      
      get performanceColor() {
        const percentage = (this.score / this.maxPossibleScore) * 100;
        if (percentage >= 90) return 'text-yellow-400';
        if (percentage >= 75) return 'text-green-400';
        if (percentage >= 60) return 'text-blue-400';
        if (percentage >= 40) return 'text-orange-400';
        return 'text-red-400';
      },
      
      get performanceIcon() {
        const percentage = (this.score / this.maxPossibleScore) * 100;
        if (percentage >= 90) return '🏆';
        if (percentage >= 75) return '⭐';
        if (percentage >= 60) return '👍';
        if (percentage >= 40) return '💪';
        return '📚';
      },
      
      get performanceMessage() {
        const percentage = (this.score / this.maxPossibleScore) * 100;
        if (percentage >= 90) return 'You absolutely crushed it! Quiz mastery achieved!';
        if (percentage >= 75) return 'Outstanding performance! You really know your stuff!';
        if (percentage >= 60) return 'Solid effort! You clearly understand the material.';
        if (percentage >= 40) return 'You got this! A little more practice and you\'ll ace it!';
        return 'Every expert was once a beginner. Keep going!';
      },
      
      playAgain() {
        window.location.href = 'difficulty.html';
      },
      
      goToMenu() {
        window.location.href = 'index.php';
      },
      
      createConfetti() {
        this.showConfetti = true;
        setTimeout(() => {
          this.showConfetti = false;
        }, 5000);
      }
    }"
    x-cloak
    class="w-full max-w-md mx-auto text-center relative overflow-hidden"
  >
    <!-- Loading State -->
    <template x-if="isLoading">
      <div class="bg-white/10 backdrop-blur-md p-8 rounded-xl shadow-2xl border border-white/20 text-white">
        <div class="flex flex-col items-center justify-center space-y-4">
          <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-xl">Loading your results...</p>
        </div>
      </div>
    </template>

    <!-- Error State -->
    <template x-if="error && !isLoading">
      <div class="bg-white/10 backdrop-blur-md p-8 rounded-xl shadow-2xl border border-white/20 text-white">
        <div class="flex flex-col items-center justify-center space-y-4">
          <div class="text-red-500 text-4xl">⚠️</div>
          <p class="text-xl" x-text="error"></p>
          <button @click="goToMenu" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
            Return to Menu
          </button>
        </div>
      </div>
    </template>

    <!-- Results Card -->
    <template x-if="!isLoading && !error">
      <div>
     <!-- Confetti Effect -->
     <template x-if="showConfetti">
          <div class="fixed inset-0 pointer-events-none">
            <template x-for="i in 100">
              <div class="confetti" 
                   :style="`
                     left: ${Math.random() * 100}%;
                     top: ${Math.random() * 100}%;
                     background-color: hsl(${Math.random() * 360}, 100%, 50%);
                     transform: rotate(${Math.random() * 360}deg);
                     width: ${Math.random() * 10 + 5}px;
                     height: ${Math.random() * 10 + 5}px;
                     animation: fall ${Math.random() * 3 + 2}s linear infinite;
                   `"
                   :class="`confetti-${i}`"></div>
            </template>
          </div>
        </template>

        <div class="bg-white/10 backdrop-blur-md p-8 rounded-xl shadow-2xl border border-white/20 text-white">
          <!-- Performance Rating -->
          <div class="text-6xl mb-4 trophy" x-text="performanceIcon"></div>
          <h1 class="text-4xl font-bold mb-2" x-text="performanceRating" :class="performanceColor"></h1>
          
          <!-- Score Details -->
          <div class="mb-6">
            <p class="text-xl" x-text="performanceMessage"></p>
          </div>
          
          <!-- Player Name -->
          <div class="w-100 flex items-center justify-center">
            <p class="text-lg text-white mb-2">Player: <span x-text="username" class="font-bold"></span></p>
          </div>

          <!-- Animated Progress Bar -->
          <div class="mb-8">
            <div class="flex justify-between mb-2">
              <span class="text-lg font-medium">Correct Answers:</span>
              <span class="text-lg font-bold" x-text="`${correctAnswers}/${totalQuestions}`"></span>
            </div>
            
            <div class="progress-container">
              <div class="progress-bar" :style="`width: ${progressWidth}%`">
                <div class="progress-thumb">
                  <div class="percentage-display" x-text="`${Math.round(progressWidth)}%`"></div>
                </div>
              </div>
            </div>
            
            <div class="progress-labels">
              <span>0%</span>
              <span>50%</span>
              <span>100%</span>
            </div>
          </div>

          <!-- Score Details -->
          <div class="bg-white/20 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
              <span class="text-lg">Base Score:</span>
              <span class="text-xl font-bold" x-text="correctAnswers * baseScorePerQuestion"></span>
            </div>
            <template x-if="timeBonus > 0">
              <div class="flex justify-between items-center mb-2">
                <span class="text-lg">Time Bonus:</span>
                <span class="text-xl font-bold text-green-400" x-text="`+${timeBonus}`"></span>
              </div>
            </template>
            <div class="flex justify-between items-center mb-2 border-t border-white/20 pt-2">
              <span class="text-lg font-bold">Total Score:</span>
              <span class="text-2xl font-bold" x-text="score"></span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-lg">Max Possible:</span>
              <span class="text-xl" x-text="maxPossibleScore"></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-lg">Difficulty:</span>
              <span class="text-xl font-bold capitalize" x-text="difficulty"></span>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="flex space-x-4 justify-center">
            <button @click="playAgain" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center shadow-md hover:shadow-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Play Again
            </button>
            <button @click="goToMenu" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition flex items-center shadow-md hover:shadow-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              Main Menu
            </button>
          </div>
        </div>      </div>
    </template>
  </div>

  <script>
    setTimeout(() => {
      const video1 = document.getElementById('forest');
      const video2 = document.getElementById('waterfalls');

      video1.classList.add('translate-x-[-100%]');
      video2.classList.remove('translate-x-full');
      video2.classList.add('translate-x-0');
    }, 700);
  </script>
</body>
</html>