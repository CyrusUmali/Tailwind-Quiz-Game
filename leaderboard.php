<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Game - Leaderboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak] {
      display: none !important;
    }

    /* Medal animations */
    @keyframes goldGlow {

      0%,
      100% {
        transform: scale(1);
        filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.7));
      }

      50% {
        transform: scale(1.05);
        filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.9));
      }
    }

    @keyframes silverGlow {

      0%,
      100% {
        transform: scale(1);
        filter: drop-shadow(0 0 5px rgba(192, 192, 192, 0.7));
      }

      50% {
        transform: scale(1.03);
        filter: drop-shadow(0 0 10px rgba(192, 192, 192, 0.9));
      }
    }

    @keyframes bronzeGlow {

      0%,
      100% {
        transform: scale(1);
        filter: drop-shadow(0 0 5px rgba(205, 127, 50, 0.7));
      }

      50% {
        transform: scale(1.02);
        filter: drop-shadow(0 0 8px rgba(205, 127, 50, 0.9));
      }
    }

    .gold-medal {
      animation: goldGlow 2s infinite ease-in-out;
    }

    .silver-medal {
      animation: silverGlow 2s infinite ease-in-out;
    }

    .bronze-medal {
      animation: bronzeGlow 2s infinite ease-in-out;
    }

    /* Entry animation */
    @keyframes slideIn {
      from {
        transform: translateY(20px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .leaderboard-entry {
      animation: slideIn 0.5s ease-out forwards;
    }
  </style>
</head>

<body class="min-h-screen bg-gray-900 text-white">
  <!-- Video Background -->
  <video autoplay muted loop class="fixed top-0 left-0 w-full h-full object-cover -z-10">
    <source src="image/Forest_BG.PNG" type="video/mp4">
  </video>

  <!-- Main Content -->
  <div
    x-data="{
      leaderboard: [],
      difficultyFilter: 'all',
      isLoading: true,
      soundEnabled: true,
      backgroundMusic: null,
      username: 'Guest',
      currentUser: null,
      error: null,
      
      init() {
        this.setupAudio();
        this.checkCurrentUser().then(() => {
          this.loadLeaderboard();
        });
      },
      
      setupAudio() {
        try {
          this.backgroundMusic = new Audio('background-musicc.mp3');
          this.backgroundMusic.loop = true;
          
          // Try to auto-play background music
          this.backgroundMusic.play().then(() => {
            this.soundEnabled = true;
          }).catch(() => {
            this.soundEnabled = false;
          });
        } catch (e) {
          console.error('Audio setup failed:', e);
          this.soundEnabled = false;
        }
      },
      
      toggleSound() {
        if (!this.backgroundMusic) return;

        if (this.soundEnabled) {
          this.backgroundMusic.pause();
        } else {
          this.backgroundMusic.play().catch(e => console.error('Audio play failed:', e));
        }

        this.soundEnabled = !this.soundEnabled;
      },
      
      playButtonSound() {
        if (this.soundEnabled) {
          try {
            new Audio('button-sound.mp3').play().catch(e => console.error('Button sound failed:', e));
          } catch (e) {
            console.error('Button sound setup failed:', e);
          }
        }
      },
      
    async checkCurrentUser() {
  try {
    const response = await axios.get('http://localhost/tailwind_quiz_game/server/routes/api.php/auth/check', {
      withCredentials: true
    });
    
    if (response.data && response.data.authenticated) {
      this.currentUser = response.data.user;
      this.username = this.currentUser.username;
      // Ensure ID is properly set
      this.currentUser.id = response.data.user._id?.$oid || response.data.user.id;
    }
  } catch (error) {
    console.error('Error checking auth status:', error);
    this.error = 'Failed to check user authentication';
  }
},
      
     async loadLeaderboard() {
    this.isLoading = true;
    this.error = null;
    
    try {
        const params = this.difficultyFilter !== 'all' ? { difficulty: this.difficultyFilter } : {};
        
        const response = await axios.get(
            'http://localhost/tailwind_quiz_game/server/routes/api.php/leaderboard',
            { 
                params,
                withCredentials: true
            }
        );
        
        // Handle the new consistent response format
        if (response.data && response.data.success && Array.isArray(response.data.data)) {
            this.leaderboard = response.data.data;
        } else {
            console.error('Unexpected leaderboard format:', response.data);
            this.error = 'Invalid leaderboard data format';
            this.leaderboard = [];
        }
    } catch (error) {
        console.error('Error loading leaderboard:', error);
        this.error = 'Failed to load leaderboard';
        this.leaderboard = [];
    } finally {
        this.isLoading = false;
    }
},
      
      get filteredLeaderboard() {
        // Server already filters by difficulty, we just sort here
        return [...this.leaderboard].sort((a, b) => b.score - a.score);
      },
      
      get medalClass() {
        return (index) => {
          if (index === 0) return 'gold-medal';
          if (index === 1) return 'silver-medal';
          if (index === 2) return 'bronze-medal';
          return '';
        };
      },
      
      get medalIcon() {
        return (index) => {
          if (index === 0) return '🥇';
          if (index === 1) return '🥈';
          if (index === 2) return '🥉';
          return (index + 1) + '.';
        };
      },
      
      get difficultyColor() {
        return (difficulty) => {
          return {
            'easy': 'bg-green-600',
            'medium': 'bg-yellow-600',
            'hard': 'bg-red-600'
          }[difficulty] || 'bg-gray-600';
        };
      },
      
    get currentPlayerBest() {
  if (!this.currentUser) return null;
  
  const playerEntries = this.leaderboard.filter(entry => {
    // Handle both MongoDB object ID and plain string ID cases
    const entryUserId = entry.userId?.$oid || entry.userId?.id || entry.userId;
    return entryUserId && entryUserId.toString() === this.currentUser.id.toString();
  });

  if (playerEntries.length === 0) return null;

  const bestEntry = playerEntries.reduce((max, entry) => 
    entry.score > max.score ? entry : max
  );

  // Find the rank by comparing scores (since IDs might not match exactly)
  const sortedLeaderboard = [...this.leaderboard].sort((a, b) => b.score - a.score);
  const rank = sortedLeaderboard.findIndex(e => e.score === bestEntry.score) + 1;

  return {
    ...bestEntry,
    rank: rank
  };
},
      calculateAccuracy(entry) {
    if (!entry || typeof entry.correctAnswers === 'undefined' || !entry.totalQuestions || entry.totalQuestions === 0) {
        return 0;
    }
    return Math.round((entry.correctAnswers / entry.totalQuestions) * 100);
},
      
      goToMenu() {
        this.playButtonSound();
        window.location.href = 'index.php';
      },
      
      playAgain() {
        this.playButtonSound();
        window.location.href = 'difficulty.html';
      }
    }"
    x-cloak
    class="container mx-auto px-4 py-8">
    <!-- Sound Toggle -->
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

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold mb-2 text-green-400">Forest Quiz Leaderboard</h1>
      <p class="text-lg text-gray-300">Top performers in the enchanted forest</p>

      <!-- Difficulty Filter -->
      <div class="flex justify-center mt-6 space-x-2">
        <button
          @click="difficultyFilter = 'all'; loadLeaderboard(); playButtonSound()"
          :class="{'bg-green-700': difficultyFilter === 'all'}"
          class="px-4 py-2 bg-gray-700 rounded-lg transition">
          All
        </button>
        <button
          @click="difficultyFilter = 'easy'; loadLeaderboard(); playButtonSound()"
          :class="{'bg-green-600': difficultyFilter === 'easy'}"
          class="px-4 py-2 bg-gray-700 rounded-lg transition">
          Easy
        </button>
        <button
          @click="difficultyFilter = 'medium'; loadLeaderboard(); playButtonSound()"
          :class="{'bg-yellow-600': difficultyFilter === 'medium'}"
          class="px-4 py-2 bg-gray-700 rounded-lg transition">
          Medium
        </button>
        <button
          @click="difficultyFilter = 'hard'; loadLeaderboard(); playButtonSound()"
          :class="{'bg-red-600': difficultyFilter === 'hard'}"
          class="px-4 py-2 bg-gray-700 rounded-lg transition">
          Hard
        </button>
      </div>
    </div>

    <!-- Error Message -->
    <template x-if="error">
      <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 mb-8 text-center">
        <p x-text="error" class="text-red-200"></p>
        <button @click="loadLeaderboard()" class="mt-2 px-4 py-2 bg-red-700 hover:bg-red-600 rounded transition">
          Retry
        </button>
      </div>
    </template>

    <!-- Loading State -->
    <template x-if="isLoading && !error">
      <div class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green-500 mb-4"></div>
        <p>Loading forest leaderboard...</p>
      </div>
    </template>

    <!-- Leaderboard Content -->
    <template x-if="!isLoading && !error">
      <div class="max-w-4xl mx-auto">
        <!-- Top 3 Podium -->
        <div class="grid grid-cols-3 gap-4 mb-8" x-show="filteredLeaderboard.length >= 3 && difficultyFilter === 'all'">
          <!-- 2nd Place -->
          <div class="flex flex-col items-center justify-end">
            <div class="w-full bg-gray-700 rounded-t-lg p-4 text-center h-[155px] flex flex-col justify-end">
              <template x-if="filteredLeaderboard[1]">
                <div>
                  <span class="text-3xl mb-2 silver-medal">🥈</span>
                  <h3 x-text="filteredLeaderboard[1].username" class="font-bold text-lg truncate w-full"></h3>
                  <p x-text="filteredLeaderboard[1].score" class="text-xl font-mono"></p>
                  <div class="flex justify-center mt-1 space-x-1">
                    <span :class="difficultyColor(filteredLeaderboard[1].difficulty)"
                      class="inline-block px-2 py-1 rounded-full text-xs font-bold capitalize"
                      x-text="filteredLeaderboard[1].difficulty"></span>
                    <span class="bg-blue-600 text-white px-2 py-1 rounded-full text-xs font-bold"
                      x-text="`${calculateAccuracy(filteredLeaderboard[1])}%`"></span>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- 1st Place -->
          <div class="flex flex-col items-center justify-end">
            <div class="w-full bg-gray-800 rounded-t-lg p-4 text-center h-[180px] flex flex-col justify-end border-b-4 border-yellow-400">
              <template x-if="filteredLeaderboard[0]">
                <div>
                  <span class="text-4xl mb-2 gold-medal">🥇</span>
                  <h3 x-text="filteredLeaderboard[0].username" class="font-bold text-xl truncate w-full"></h3>
                  <p x-text="filteredLeaderboard[0].score" class="text-2xl font-mono"></p>
                  <div class="flex justify-center mt-1 space-x-1">
                    <span :class="difficultyColor(filteredLeaderboard[0].difficulty)"
                      class="inline-block px-2 py-1 rounded-full text-xs font-bold capitalize"
                      x-text="filteredLeaderboard[0].difficulty"></span>
                    <span class="bg-blue-600 text-white px-2 py-1 rounded-full text-xs font-bold"
                      x-text="`${calculateAccuracy(filteredLeaderboard[0])}%`"></span>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- 3rd Place -->
          <div class="flex flex-col items-center justify-end">
            <div class="w-full bg-gray-700 rounded-t-lg p-4 text-center h-[155px] flex flex-col justify-end">
              <template x-if="filteredLeaderboard[2]">
                <div>
                  <span class="text-2xl mb-2 bronze-medal">🥉</span>
                  <h3 x-text="filteredLeaderboard[2].username" class="font-bold text-lg truncate w-full"></h3>
                  <p x-text="filteredLeaderboard[2].score" class="text-xl font-mono"></p>
                  <div class="flex justify-center mt-1 space-x-1">
                    <span :class="difficultyColor(filteredLeaderboard[2].difficulty)"
                      class="inline-block px-2 py-1 rounded-full text-xs font-bold capitalize"
                      x-text="filteredLeaderboard[2].difficulty"></span>
                    <span class="bg-blue-600 text-white px-2 py-1 rounded-full text-xs font-bold"
                      x-text="`${calculateAccuracy(filteredLeaderboard[2])}%`"></span>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Leaderboard List -->
        <div class="bg-white/10 backdrop-blur-md rounded-xl overflow-hidden border border-white/20">
          <div class="grid grid-cols-12 bg-white/20 py-3 px-4 font-bold text-sm uppercase tracking-wider">
            <div class="col-span-1">Rank</div>
            <div class="col-span-4">Player</div>
            <div class="col-span-2 text-right">Score</div>
            <div class="col-span-2 text-center">Difficulty</div>
            <div class="col-span-2 text-center">Accuracy</div>
            <div class="col-span-1 text-right">Questions</div>
          </div>

          <div class="divide-y divide-white/10">
            <template x-for="(entry, index) in filteredLeaderboard" :key="index">
              <div
                class="grid grid-cols-12 py-3 px-4 items-center hover:bg-white/5 transition leaderboard-entry"
                :style="`animation-delay: ${index * 0.05}s`"
                :class="{'bg-green-900/30': currentUser && entry.userId === currentUser.id}">
                <div class="col-span-1 font-bold" x-text="medalIcon(index)"></div>
                <div class="col-span-4 font-medium truncate" x-text="entry.username"></div>
                <div class="col-span-2 text-right font-mono" x-text="entry.score"></div>
                <div class="col-span-2 text-center">
                  <span
                    :class="difficultyColor(entry.difficulty)"
                    class="inline-block px-2 py-1 rounded-full text-xs font-bold capitalize"
                    x-text="entry.difficulty"></span>
                </div>
                <div class="col-span-2 text-center font-mono" x-text="`${calculateAccuracy(entry)}%`"></div>
                <div class="col-span-1 text-right text-sm" x-text="entry.totalQuestions"></div>
              </div>
            </template>

            <template x-if="filteredLeaderboard.length === 0">
              <div class="py-8 text-center text-gray-400">
                No entries found for this difficulty level.
              </div>
            </template>
          </div>
        </div>

        <!-- Current Player Stats -->
        <template x-if="currentPlayerBest">
          <div class="mt-8 bg-green-900/50 rounded-lg p-4 border border-green-700">
            <h3 class="text-lg font-bold mb-2 text-green-300">Your Best Performance</h3>
            <div class="grid grid-cols-12 py-3 px-4 items-center bg-white/5 rounded">
              <div class="col-span-1 font-bold" x-text="currentPlayerBest.rank"></div>
              <div class="col-span-4 font-medium" x-text="currentPlayerBest.username"></div>
              <div class="col-span-2 text-right font-mono" x-text="currentPlayerBest.score"></div>
              <div class="col-span-2 text-center">
                <span
                  :class="difficultyColor(currentPlayerBest.difficulty)"
                  class="inline-block px-2 py-1 rounded-full text-xs font-bold capitalize"
                  x-text="currentPlayerBest.difficulty"></span>
              </div>
              <div class="col-span-2 text-center font-mono" x-text="`${calculateAccuracy(currentPlayerBest)}%`"></div>
              <div class="col-span-1 text-right text-sm" x-text="currentPlayerBest.totalQuestions"></div>
            </div>
          </div>
        </template>
      </div>
    </template>

    <!-- Action Buttons -->
    <div class="flex justify-center mt-8 space-x-4">
      <button
        @click="playAgain"
        @mousedown="playButtonSound"
        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold transition flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Play Again
      </button>
 

      <button
        @click="goToMenu"
        @mousedown="playButtonSound"
        class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-bold transition flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Main Menu
      </button>
    </div>
  </div>
</body>

</html>