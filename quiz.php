<?php
 
 session_start();
// Initialize user ID variable
$userId = null;


// Redirect to index if user is not authenticated
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
  header("Location: index.php");
  exit(); // Stop further script execution
}

// Check if user is logged in
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
}

if (!isset($_SESSION['quizSessionId'])) {
  $_SESSION['quizSessionId'] = bin2hex(random_bytes(16)); // Generates a 32-character hex string
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Game</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak] { display: none !important; }
    
    /* Flame Animation Styles */
    @keyframes flameAppear {
      0% { transform: scale(0.8) translateY(20px); opacity: 0; }
      100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    
    @keyframes flameSustain {
      0%, 100% { transform: scale(1) rotate(-1deg); }
      50% { transform: scale(1.03) rotate(1deg); }
    }
    
    @keyframes flameDisappear {
      0% { transform: scale(1) translateY(0); opacity: 1; }
      100% { transform: scale(0.8) translateY(20px); opacity: 0; }
    }
    
    .flame-appear { animation: flameAppear 0.5s ease-out forwards; }
    .flame-sustain { animation: flameSustain 1.5s ease-in-out infinite; }
    .flame-disappear { animation: flameDisappear 0.4s ease-in forwards; }
    
    .counter-pop {
      animation: pop 0.3s 0.4s both;
    }
    
    @keyframes pop {
      0% { transform: translate(-50%, -50%) scale(0); }
      80% { transform: translate(-50%, -50%) scale(1.2); }
      100% { transform: translate(-50%, -50%) scale(1); }
    }
    
    /* Navigation button styles */
    .nav-btn {
      transition: all 0.2s ease;
    }
    .nav-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    .nav-btn:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="relative h-screen flex items-center justify-center">
  <!-- Video Background -->
  <video autoplay muted loop class="absolute left-0 w-full h-full object-cover -z-10">
    <source src="video/username_background.mp4" type="video/mp4">
  </video>


  <script>
  // Pass PHP variable to JS first
  window.quizData = { 
    difficulty: localStorage.getItem('difficulty') || 'easy',
    questionCount: parseInt(localStorage.getItem('questionCount')) || 5
  };
</script>

  <div x-data="{
     difficulty: window.quizData.difficulty,
    questionCount: window.quizData.questionCount,
    currentQuestion: 0,
    score: 0,
    sessionId: null,  
    consecutiveCorrect: 0,
    selectedOption: null,
        isCurrentQuestionAnswered: false,  
    timeLeft: 300,  
    timerInterval: null,
    isLoading: false,
    error: null,
    quizFinished: false,
    
    // Track answered questions
    answeredQuestions: [],
    userAnswers: [],
    
    // Flame animation state
    showFlame: false,
    streakCount: 3,
    currentAnimation: '',
    
    currentQuestions: [],
    
    async init() {
      this.isLoading = true;
      try {
      this.timeLeft = this.calculateTotalTime(); // Set initial time
        await this.fetchQuestions();
        this.startTimer();
      } catch (error) {
        this.error = 'Failed to load questions. Please try again.';
        console.error('Error loading questions:', error);
      } finally {
        this.isLoading = false;
      }
    },
    
    async fetchQuestions() {
      try {
        const response = await axios({
          method: 'get',
          url: 'http://localhost/tailwind_quiz_game/server/routes/api.php/questions',
          params: {
            difficulty: this.difficulty,
            count: this.questionCount
          },
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });
        
        // Transform the response data to match our expected format
        this.currentQuestions = response.data.map(question => ({
          id: question.id,
          question: question.question,
          options: question.options,
          answer: question.correctAnswer, // This will be the index of the correct answer
        }));
        
        this.shuffleAnswers();
        
        // Initialize answeredQuestions and userAnswers arrays
        this.answeredQuestions = new Array(this.currentQuestions.length).fill(false);
        this.userAnswers = new Array(this.currentQuestions.length).fill(null);
      } catch (error) {
        console.error('Error fetching questions:', error);
        throw error;
      }
    },
    
    shuffleAnswers() {
      this.currentQuestions.forEach(question => {
        const originalAnswerIndex = question.answer;
        const originalOptions = [...question.options];
        
        for (let i = question.options.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [question.options[i], question.options[j]] = [question.options[j], question.options[i]];
        }
        
        question.answer = question.options.indexOf(originalOptions[originalAnswerIndex]);
      });
    },
    
    startTimer() {
      clearInterval(this.timerInterval);
      this.timerInterval = setInterval(() => {
        this.timeLeft--;
        if (this.timeLeft <= 0) {
          this.finishQuiz();
        }
      }, 1000);
    },
    
  selectOption(index) {
        // Don't allow changing answers for already answered questions
        if (this.answeredQuestions[this.currentQuestion]) {
            return;
        }
        
        this.selectedOption = index;
        this.userAnswers[this.currentQuestion] = index;
        const isCorrect = index === this.currentQuestions[this.currentQuestion].answer;
        
        if (isCorrect) {
            const difficultyMultiplier = this.difficulty === 'easy' ? 1 : 
                                     this.difficulty === 'medium' ? 2 :  
                                     this.difficulty === 'hard' ? 5 : 1;
            this.score += 100 * difficultyMultiplier;
            this.consecutiveCorrect++;
            
            if (this.consecutiveCorrect >= 3) {
                this.triggerFlameAnimation();
                this.consecutiveCorrect = 0;
            }
        } else {
            this.consecutiveCorrect = 0;
        }
        
        // Mark question as answered
        this.answeredQuestions[this.currentQuestion] = true;
        this.isCurrentQuestionAnswered = true;
        
        // Store the user's answer
        this.storeUserAnswer(isCorrect);
    },
    
    async storeUserAnswer(isCorrect) {
      try {
        // Generate a unique session ID if it doesn't exist
        if (!this.sessionId) {
          this.sessionId = Date.now().toString(36) + Math.random().toString(36).substring(2);
        }
        
        await axios({
          method: 'post',
          url: 'http://localhost/tailwind_quiz_game/server/routes/api.php/answers',
          data: { 
            questionId: this.currentQuestions[this.currentQuestion].id,
            questionNumber: this.currentQuestion + 1, // Current question position
            totalQuestions: this.questionCount, // Total questions in this session
            userAnswer: this.selectedOption, 
            isCorrect: isCorrect,
            difficulty: this.difficulty,
            timeLeft: this.timeLeft, // How much time was remaining
            timestamp: new Date().toISOString()
          },
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });
      } catch (error) {
        console.error('Error storing answer:', error);
      }
    },
    
   goToQuestion(index) {
        this.currentQuestion = index;
        this.selectedOption = this.userAnswers[this.currentQuestion];
        this.isCurrentQuestionAnswered = this.answeredQuestions[this.currentQuestion];
    },
    
    nextQuestion() {
      if (this.currentQuestion < this.currentQuestions.length - 1) {
        this.currentQuestion++;
        this.selectedOption = this.userAnswers[this.currentQuestion];
      }
    },
    
    prevQuestion() {
      if (this.currentQuestion > 0) {
        this.currentQuestion--;
        this.selectedOption = this.userAnswers[this.currentQuestion];
      }
    },
    
    finishQuiz() {
      clearInterval(this.timerInterval);
      this.quizFinished = true;
      localStorage.setItem('finalScore', this.score);
      localStorage.setItem('totalQuestions', this.currentQuestions.length);
      window.location.href = 'result.php';
    },
    
    getVisibleQuestionIndices() {
      const visibleIndices = [];
      const range = 2; // Number of buttons to show on each side of current
      const start = Math.max(0, this.currentQuestion - range);
      const end = Math.min(this.currentQuestions.length - 1, this.currentQuestion + range);
      
      for (let i = start; i <= end; i++) {
        visibleIndices.push(i);
      }
      
      return visibleIndices;
    },


    calculateTotalTime() {
  const timePerQuestion = {
    easy: 15,
    medium: 10,
    hard: 5
  };
  
  return this.questionCount * timePerQuestion[this.difficulty];
},


    triggerFlameAnimation() {
      this.currentAnimation = 'appear';
      this.showFlame = true;
      
      setTimeout(() => {
        this.currentAnimation = 'sustain';
      }, 500);
      
      setTimeout(() => {
        this.currentAnimation = 'disappear';
        setTimeout(() => {
          this.showFlame = false;
        }, 400);
      }, 3000);
    },
    
    // Helper to check if all questions are answered
    allQuestionsAnswered() {
      return this.answeredQuestions.every(q => q);
    },
    
    // Helper to get question status for styling
    getQuestionStatus(index) {
      if (index === this.currentQuestion) return 'current';
      if (this.answeredQuestions[index]) return 'answered';
      return 'unanswered';
    },
    
    formatTime(seconds) {
      const mins = Math.floor(seconds / 60);
      const secs = seconds % 60;
      return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }
  }" x-init="init()" x-cloak class="w-full max-w-md mx-auto relative">
    
    <!-- Loading State -->
    <div x-show="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 mx-auto mb-4"></div>
        <p class="text-lg font-medium">Loading questions...</p>
      </div>
    </div>
    
    <!-- Error State -->
    <div x-show="error && !isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg text-center max-w-md">
        <p class="text-red-500 text-lg font-medium mb-4" x-text="error"></p>
        <button @click="init()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
          Try Again
        </button>
      </div>
    </div>
    
    <!-- Main Quiz Container -->
    <div x-show="!isLoading && !error && currentQuestions.length > 0 && !quizFinished" class="bg-white rounded-lg shadow-lg overflow-hidden relative z-10">
      
      <div class="bg-green-600 p-4 text-white flex justify-between items-center">
        <div x-text="`Question ${currentQuestion + 1}/${questionCount}`" class="font-medium"></div>
        <div class="flex items-center space-x-4">
          <div x-text="formatTime(timeLeft)" class="font-medium"></div>
          <div x-text="`Score: ${score}`" class="font-bold"></div>
          <div x-show="consecutiveCorrect > 0" 
               class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
            Streak: <span x-text="consecutiveCorrect"></span>
          </div>
        </div>
      </div>
      
      <!-- Question Navigation -->
      <div class="bg-gray-100 p-2 flex justify-center space-x-1 overflow-x-auto">
        <!-- Previous button -->
        <button
          @click="goToQuestion(currentQuestion - 1)"
          :disabled="currentQuestion === 0"
          class="w-8 h-8 rounded-full text-sm font-medium flex items-center justify-center nav-btn bg-gray-200 disabled:opacity-50"
        >
          &lt;
        </button>
      
        <!-- First page button (shown when not near start) -->
        <button
          x-show="currentQuestion > 2"
          @click="goToQuestion(0)"
          :class="{
            'bg-green-600 text-white': getQuestionStatus(0) === 'current',
            'bg-green-100 text-green-800': getQuestionStatus(0) === 'answered',
            'bg-gray-200 text-gray-700': getQuestionStatus(0) === 'unanswered'
          }"
          class="w-8 h-8 rounded-full text-sm font-medium flex items-center justify-center nav-btn"
          x-text="1"
        ></button>
      
        <!-- Ellipsis (shown when gap exists) -->
        <span 
          x-show="currentQuestion > 3"
          class="w-8 h-8 flex items-center justify-center"
        >...</span>
      
        <!-- Dynamic page numbers -->
        <template x-for="index in getVisibleQuestionIndices()">
          <button
            @click="goToQuestion(index)"
            :class="{
              'bg-green-600 text-white': getQuestionStatus(index) === 'current',
              'bg-green-100 text-green-800': getQuestionStatus(index) === 'answered',
              'bg-gray-200 text-gray-700': getQuestionStatus(index) === 'unanswered',
              'border-2 border-green-600': getQuestionStatus(index) === 'current'
            }"
            class="w-8 h-8 rounded-full text-sm font-medium flex items-center justify-center nav-btn"
            x-text="index + 1"
          ></button>
        </template>
      
        <!-- Ellipsis (shown when gap exists) -->
        <span 
          x-show="currentQuestion < currentQuestions.length - 4"
          class="w-8 h-8 flex items-center justify-center"
        >...</span>
      
        <!-- Last page button (shown when not near end) -->
        <button
          x-show="currentQuestion < currentQuestions.length - 3"
          @click="goToQuestion(currentQuestions.length - 1)"
          :class="{
            'bg-green-600 text-white': getQuestionStatus(currentQuestions.length - 1) === 'current',
            'bg-green-100 text-green-800': getQuestionStatus(currentQuestions.length - 1) === 'answered',
            'bg-gray-200 text-gray-700': getQuestionStatus(currentQuestions.length - 1) === 'unanswered'
          }"
          class="w-8 h-8 rounded-full text-sm font-medium flex items-center justify-center nav-btn"
          x-text="currentQuestions.length"
        ></button>
      
        <!-- Next button -->
        <button
          @click="goToQuestion(currentQuestion + 1)"
          :disabled="currentQuestion === currentQuestions.length - 1"
          class="w-8 h-8 rounded-full text-sm font-medium flex items-center justify-center nav-btn bg-gray-200 disabled:opacity-50"
        >
          &gt;
        </button>
      </div>
      
      <div class="p-6">
        <h2 x-text="currentQuestions[currentQuestion].question" class="text-xl font-bold mb-4 text-gray-800"></h2>
        <div class="space-y-3">
        <template x-for="(option, index) in currentQuestions[currentQuestion].options">
    <button
        @click="selectOption(index)"
        :class="{
            'bg-green-100 border-green-500': selectedOption === index && answeredQuestions[currentQuestion],
            'border-gray-300 hover:bg-gray-50': selectedOption !== index && !answeredQuestions[currentQuestion],
            'bg-green-100 border-green-500': answeredQuestions[currentQuestion] && 
                index === currentQuestions[currentQuestion].answer && 
                userAnswers[currentQuestion] === index,
            'bg-red-100 border-red-500': answeredQuestions[currentQuestion] && 
                userAnswers[currentQuestion] === index && 
                userAnswers[currentQuestion] !== currentQuestions[currentQuestion].answer,
            'cursor-not-allowed opacity-75': answeredQuestions[currentQuestion] && !(selectedOption === index && answeredQuestions[currentQuestion])
        }"
        class="w-full text-left p-3 border rounded transition duration-200"
        :disabled="answeredQuestions[currentQuestion] && !(selectedOption === index && answeredQuestions[currentQuestion])"
        x-text="option"
    ></button>
</template>
        </div>
      </div>
      
      <div class="p-4 bg-gray-50 flex justify-between items-center">
        <div class="flex space-x-2">
          <button
            @click="prevQuestion"
            :disabled="currentQuestion === 0"
            class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 
                   disabled:opacity-50 transition nav-btn"
          >
            Previous
          </button>
          
        </div>
        
        <div x-show="answeredQuestions[currentQuestion]" class="text-sm text-gray-600">
          <span x-show="userAnswers[currentQuestion] === currentQuestions[currentQuestion].answer" 
                class="text-green-600 font-medium">Correct!</span>
          <span x-show="userAnswers[currentQuestion] !== null && 
                        userAnswers[currentQuestion] !== currentQuestions[currentQuestion].answer" 
                class="text-red-600 font-medium">Incorrect!</span>
        </div>
        
        <button
          @click="finishQuiz"
          class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
        >
          Finish Quiz
        </button>
      </div>
    </div>
    
    <!-- Flame Animation Overlay -->
    <div x-show="showFlame" 
         x-transition.opacity.duration.300
         class="fixed inset-0 bg-black bg-opacity-70 z-20 flex items-center justify-center">
    </div>
    
    <!-- Flame Animation -->
    <div x-show="showFlame"
         :class="{
           'flame-appear': currentAnimation === 'appear',
           'flame-sustain': currentAnimation === 'sustain',
           'flame-disappear': currentAnimation === 'disappear'
         }"
         class="fixed inset-0 flex items-center justify-center z-30 pointer-events-none">
      <div class="relative">
        <video 
          autoplay loop muted playsinline
          class="mega-flame object-contain drop-shadow-[0_0_30px_rgba(255,80,0,0.9)]"
        >
          <source src="fireee.webm" type="video/webm">
          Your browser doesn't support videos.
        </video>
        <span x-show="currentAnimation !== 'disappear'"
              class="absolute bottom-8 left-1/2 transform -translate-x-1/2 
                     bg-gradient-to-br from-red-500 to-red-600 text-white 
                     rounded-full w-12 h-12 flex items-center justify-center 
                     text-lg font-bold shadow-lg border-2 border-white counter-pop">
          <span x-text="streakCount"></span>
        </span>
      </div>
    </div>
  </div>
</body>
</html>