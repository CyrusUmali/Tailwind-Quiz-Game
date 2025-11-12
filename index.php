<?php
require_once __DIR__ . '/server/config/db.php';
session_start();




?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Game - Main Menu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <style>
    [x-cloak] {
      display: none !important;
    }

    /* Enhanced Modal Animation: Fade, Scale, and Slide-in */
    .modal-enter {
      animation: modalEnter 0.5s ease-out forwards;
    }

    @keyframes modalEnter {
      0% {
        opacity: 0;
        transform: scale(0.8) translateY(-20px);
      }

      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    /* Text Animation for Introduction and Rules */
    .text-animate {
      animation: textAnim 1s ease-out forwards;
    }

    @keyframes textAnim {
      0% {
        opacity: 0;
        transform: translateY(10px);
      }

      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .sound-icon {
      transition: all 0.3s ease;
    }
  </style>
  <!-- Tailwind Cloud effect-->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          animation: {
            slideLeftFade: "slideLeftFade 3s ease-in-out forwards",
            slideRightFade: "slideRightFade 3s ease-in-out forwards"
          },
          keyframes: {
            slideLeftFade: {
              "0%": {
                transform: "translateX(0)",
                opacity: "1"
              },
              "100%": {
                transform: "translateX(-100%)",
                opacity: "0"
              }
            },
            slideRightFade: {
              "0%": {
                transform: "translateX(0)",
                opacity: "1"
              },
              "100%": {
                transform: "translateX(100%)",
                opacity: "0"
              }
            }
          }
        }
      }
    }
  </script>

</head>

<body class="relative h-screen flex items-center justify-center overflow-hidden">

  <!-- Video Background -->
  <video autoplay muted loop class="fixed top-0 left-0 w-full h-full object-cover -z-10">
    <source src="image/Forest_BG.PNG" type="video/mp4">
  </video>

  <!-- for Birds -->
  <iframe
    class="absolute w-full h-full z-20 pointer-events-none"
    src="https://lottie.host/embed/f7ab1a2a-f011-4f9a-9e6e-586aa813280f/oaQ3OlzewR.lottie"></iframe>

  <!-- Clouds Picture -->
  <div id="cloud1" class="absolute left-10 w-500 h-auto z-40 absolute top-20 animate-slideLeftFade">
    <img src="image/Cloud.png" class="w-500 h-auto" />
  </div>

  <div id="cloud2" class="absolute left-[350px] w-[300px] h-auto z-30 absolute top-20 animate-slideLeftFade">
    <img src="image/Cloud.png" class="w-500 h-auto" />
  </div>

  <div id="cloud3" class="absolute right-10 w-500 h-auto z-40 absolute top-20 animate-slideRightFade">
    <img src="image/Cloud.png" class="w-500 h-auto" />
  </div>

  <div id="cloud4" class="absolute right-[350px] w-[300px] h-auto z-30 absolute top-20 animate-slideRightFade">
    <img src="image/Cloud.png" class="w-500 h-auto" />
  </div>

  <!-- for owls -->
  <iframe
    class="absolute mr-[655px] "
    src="https://lottie.host/embed/e4f12a8a-9e1d-43d6-b753-87a7178610d4/Asm1Lf55kC.lottie"></iframe>

  <!-- for monkey-->
  <iframe
    class="flex mb-[350px]"
    src="https://lottie.host/embed/2c6c6b72-3307-49e8-99d2-4884d912e940/5nYcIMNLO7.lottie"></iframe>

  <!-- Main Menu -->
  <div
    x-data="{
      soundEnabled: true,
      showIntro: false,
      showRules: false,
      showAuthModal: false,
      isLoginView: true,
      email: '',
      username: '',
      password: '',
      authError: '',
      isLoading: false,
      isAuthenticated: false,
      currentUser: null,
      backgroundMusic: null,
      
      init() {


      axios.defaults.withCredentials = true;
        // Check auth status on load
        this.checkAuthStatus();
        
        // Initialize background music
        this.backgroundMusic = new Audio('background-musicc.mp3');
        this.backgroundMusic.loop = true;
        
        this.backgroundMusic.play().then(() => {
          this.soundEnabled = true;
        }).catch(() => {
          this.soundEnabled = false;
        });
      },

      async checkAuthStatus() {
        try { 
            const response = await axios.get(
                'http://localhost/tailwind_quiz_game/server/routes/api.php/auth/check', 
                { withCredentials: true }
            );
            this.isAuthenticated = response.data.authenticated;
            this.currentUser = response.data.user;
        } catch (error) {
            console.error('Auth check failed:', error);
            this.isAuthenticated = false;
            this.currentUser = null;
        }
    },

      toggleSound() {
        if (!this.backgroundMusic) return;

        if (this.soundEnabled) {
          this.backgroundMusic.pause();
        } else {
          this.backgroundMusic.play();
        }

        this.soundEnabled = !this.soundEnabled;
      },
      
      playButtonSound() {
        if (this.soundEnabled) {
          new Audio('button-sound.mp3').play();
        }
      },
      
      goToLeaderboard() {
        this.playButtonSound();
        window.location.href = 'leaderboard.php';
      },
      
      startGame() {
        this.playButtonSound();
        if (!this.isAuthenticated) {
          this.showAuthModal = true;
          return;
        }
        window.location.href = 'difficulty.html';
      },
      
      submitAuth() {
        this.isLoading = true;
        this.authError = '';
        
        const endpoint = this.isLoginView 
            ? 'http://localhost/tailwind_quiz_game/server/routes/api.php/auth/login'
            : 'http://localhost/tailwind_quiz_game/server/routes/api.php/auth/register';
        
        axios({
            method: 'post',
            url: endpoint,
            data: this.isLoginView
                ? { email: this.email, password: this.password }
                : { email: this.email, username: this.username, password: this.password },
            withCredentials: true,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
       .then(response => {
    if (this.isLoginView) {
        // Login success
        this.isAuthenticated = true;
        this.currentUser = response.data.user;
        this.showAuthModal = false;
    } else {
        // Registration success - show message but keep modal open
        this.authSuccess = 'Registration successful! Please log in.';
        this.isLoginView = true; // Switch to login view
        this.email = ''; // Clear form
        this.password = '';
        this.username = '';
    }
})
       .catch(error => {
    if (error.response) {
        // Server responded with a status code that falls out of 2xx range
        this.authError = error.response.data.message || 'An error occurred';
    } else if (error.request) {
        // Request was made but no response received
        this.authError = 'No response from server';
    } else {
        // Something happened in setting up the request
        this.authError = 'Request setup error';
    }
})
        .finally(() => {
            this.isLoading = false;
        });
    },
      
    async logout() {
      try {
          await axios.post(
              'http://localhost/tailwind_quiz_game/server/routes/api.php/auth/logout', 
              {}, 
              { withCredentials: true }
          );
          this.isAuthenticated = false;
          this.currentUser = null;
          window.location.reload(); // Refresh to clear any cached state
      } catch (error) {
          console.error('Logout failed:', error);
      }
  }
    }
    
    
    "
    x-cloak
    class="absolute w-full max-w-md mx-auto text-center"
    x-init="init()">
    <div class="bg-gray-100 bg-opacity-15 p-8 rounded-xl shadow-2xl border border-gray-700">
      <h1 class="text-4xl font-bold text-white mb-8">Quiz Game





      </h1>



      <!-- Introduction Icon-->
      <button
        @click="showIntro = true"
        class="absolute top-4 left-4 p-2 rounded-full bg-gray-700 hover:bg-gray-600 transition"
        title="Introduction">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 100-20 10 10 0 000 20z" />
        </svg>
      </button>

      <!-- Rules Icon -->
      <button
        @click="showRules = true"
        class="absolute top-4 right-4 p-2 rounded-full bg-gray-700 hover:bg-gray-600 transition"
        title="Rules">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m2-3h-4a2 2 0 00-4 0H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2z" />
        </svg>
      </button>

      <!-- User Status - Moved to left side -->
      <div x-show="isAuthenticated" class="fixed  top-4 left-4 flex items-center space-x-2 bg-gray-700 bg-opacity-70 px-3 py-1 rounded-lg">
        <span class="text-white text-sm">Welcome, <span x-text="currentUser?.username" class="font-semibold"></span></span>
        <button @click="logout" class="text-white hover:text-gray-300" title="Logout">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </button>
      </div>


      <!-- Sound Toggle - Original position but adjusted for new neighbor -->
      <button
        @click="toggleSound"
        class="fixed top-4 right-4 p-2 rounded-full bg-gray-700 hover:bg-gray-600 transition z-50"
        title="Toggle Sound">
        <svg x-show="soundEnabled" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6a7.975 7.975 0 015.657 2.343m0 0a7.975 7.975 0 010 11.314m-11.314 0a7.975 7.975 0 010-11.314m0 0a7.975 7.975 0 015.657-2.343" />
        </svg>
        <svg x-show="!soundEnabled" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
      </button>

      <!-- Start Button -->
      <button
        @click="startGame"
        @mousedown="playButtonSound"
        class="w-full px-8 py-4 bg-green-600 hover:bg-blue-700 text-white text-xl font-bold rounded-lg mb-4 transition transform hover:scale-105 shadow-lg">
        START
      </button>

      <!-- Options Button -->
      <button
        @click="goToLeaderboard"
        @mousedown="playButtonSound"
        class="w-full px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition flex items-center justify-center space-x-2">
        <span>Leaderboards</span>
      </button>





    </div>

    <!-- Introduction Popup-->
    <div
      x-show="showIntro"
      x-transition:enter="modal-enter"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-green-900 p-8 rounded-xl text-white w-11/12 md:w-2/3 lg:w-1/2 text-center shadow-2xl border-4 border-green-700 animate-fade-in"
        style="background-image: url('forest-bg.jpg'); background-size: cover; background-position: center;">
        <!-- Animated Icon -->
        <div class="flex justify-center mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-400 animate-wiggle" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 100-20 10 10 0 000 20z" />
          </svg>
        </div>

        <h2 class="text-4xl font-extrabold mb-4 text-animate tracking-wide text-green-300">🌲 Welcome to the Forest Quiz Adventure! 🦉</h2>
        <p class="text-lg mb-6 text-justify leading-relaxed text-green-200 text-animate">
          Enter the heart of the enchanted forest, where wisdom and quick thinking will lead you to victory! Answer nature-inspired challenges and prove you're the ultimate explorer. 🌿🌟
        </p>

        <button
          @click="showIntro = false"
          class="mt-4 px-6 py-3 bg-amber-700 hover:bg-amber-800 rounded-lg text-lg font-bold shadow-lg transform hover:scale-105 transition-all border-2 border-yellow-500">
          Begin Your Journey! 🍃
        </button>
      </div>




    </div>

    <!-- Rules Popup (Forest Style) -->
    <div
      x-show="showRules"
      x-transition:enter="modal-enter"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-green-800 p-8 rounded-xl text-white w-11/12 md:w-2/3 lg:w-1/2 text-center shadow-2xl border-4 border-green-600 animate-fade-in"
        style="background-image: url('forest-rules.jpg'); background-size: cover; background-position: center;">
        <!-- Animated Icon -->
        <div class="flex justify-center mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-400 animate-spin-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6m2-3h-4a2 2 0 00-4 0H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2z" />
          </svg>
        </div>

        <h2 class="text-4xl font-extrabold mb-4 text-animate tracking-wide text-green-300">📜 Forest Quest Rules 🌿</h2>
        <ul class="text-lg text-left list-disc list-inside mb-6 text-animate leading-relaxed text-green-200">
          <li><strong>⏳ Time Limit:</strong> Answer quickly to maximize points!</li>
          <li><strong>🔥 Scoring:</strong> The faster you answer, the higher your score!</li>
          <li><strong>✅ Multiple Choices:</strong> Choose wisely to win big!</li>
          <li><strong>📊 Progress Tracking:</strong> Keep improving your best score!</li>
          <li><strong>🎉 Fair Play:</strong> Challenge yourself & enjoy the game!</li>
        </ul>

        <button
          @click="showRules = false"
          class="mt-4 px-6 py-3 bg-brown-700 hover:bg-brown-800 rounded-lg text-lg font-bold shadow-lg transform hover:scale-105 transition-all border-2 border-green-500">
          Got It! 🌲
        </button>
      </div>
    </div>







  <!-- Auth Modal -->
<div x-show="showAuthModal" x-transition.opacity class="fixed inset-0 flex items-center justify-center z-50 backdrop-blur-sm">
 

  <!-- Combined overlay with blur and darkness -->
  <div class="fixed inset-0 bg-black/50 backdrop-blur-md" @click="showAuthModal = false"></div>
  

<div class="w-full max-w-md mx-auto text-center" @click.away="showAuthModal = false">
    <!-- Login Form -->

    <form x-show="isLoginView" @submit.prevent="submitAuth" class="bg-white/10 backdrop-blur-md p-8 rounded-xl shadow-2xl border border-white/20 text-white">
      <!-- Trophy Icon -->
      <div class="text-6xl mb-4">🔒</div>
      <h1 class="text-4xl font-bold mb-8">Login</h1>

      <!-- Error display -->
      <div x-show="authError" class="bg-red-500/20 border border-red-500/30 text-red-200 p-3 rounded mb-6 text-left">
        <div class="font-bold mb-1">Error:</div>
        <template x-for="(error, index) in authError.split('\n')" :key="index">
          <div x-text="error" class="text-sm"></div>
        </template>
      </div>

      <div class="mb-6">
        <input
          type="email"
          x-model="email"
          placeholder="Email"
          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-white/50"
          required>
      </div>

      <div class="mb-6">
        <input
          type="password"
          x-model="password"
          placeholder="Password"
          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-white/50"
          required>
      </div>

      <button
        type="submit"
        class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center justify-center shadow-md hover:shadow-lg disabled:opacity-50"
        :disabled="isLoading">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!isLoading">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
        </svg>
        <span x-show="!isLoading">Login</span>
        <span x-show="isLoading">Processing...</span>
      </button>

      <div class="mt-6 text-center text-white/80">
        <button
          type="button"
          @click="isLoginView = false"
          class="hover:text-blue-300 transition underline underline-offset-4">
          Need an account? Register
        </button>
      </div>
    </form>

    <!-- Register Form -->
    <form x-show="!isLoginView" @submit.prevent="submitAuth" class="bg-white/10 backdrop-blur-md p-8 rounded-xl shadow-2xl border border-white/20 text-white">
      <!-- Trophy Icon -->
      <div class="text-6xl mb-4">✨</div>
      <h1 class="text-4xl font-bold mb-8">Register</h1>

      <!-- Error display -->
      <div x-show="authError" class="bg-red-500/20 border border-red-500/30 text-red-200 p-3 rounded mb-6 text-left">
        <div class="font-bold mb-1">Error:</div>
        <template x-for="(error, index) in authError.split('\n')" :key="index">
          <div x-text="error" class="text-sm"></div>
        </template>
      </div>

      <div class="mb-6">
        <input
          type="email"
          x-model="email"
          placeholder="Email"
          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-white/50"
          required>
      </div>

      <div class="mb-6">
        <input
          type="text"
          x-model="username"
          placeholder="Username"
          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-white/50"
          required>
      </div>

      <div class="mb-6">
        <input
          type="password"
          x-model="password"
          placeholder="Password"
          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-white/50"
          required>
      </div>

      <button
        type="submit"
        class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center justify-center shadow-md hover:shadow-lg disabled:opacity-50"
        :disabled="isLoading">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!isLoading">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
        </svg>
        <span x-show="!isLoading">Register</span>
        <span x-show="isLoading">Processing...</span>
      </button>

      <div class="mt-6 text-center text-white/80">
        <button
          type="button"
          @click="isLoginView = true"
          class="hover:text-blue-300 transition underline underline-offset-4">
          Have an account? Login
        </button>
      </div>
    </form>
  </div>
</div>



  </div>

  <script>
    document.getElementById('cloud1').addEventListener('animationend', function() {
      this.remove();
    });

    document.getElementById('cloud2').addEventListener('animationend', function() {
      this.remove();
    });

    document.getElementById('cloud3').addEventListener('animationend', function() {
      this.remove();
    });

    document.getElementById('cloud4').addEventListener('animationend', function() {
      this.remove();
    });
  </script>
</body>

</html>