/**
 * DialogVideo - ES6 Class for managing video dialogs with YouTube, Vimeo, and HTML5 support
 */

// API loaders
class APILoader {
    constructor() {
      this.youtubeReady = false;
      this.youtubeLoading = false;
      this.youtubeCallbacks = [];
  
      this.vimeoReady = false;
      this.vimeoLoading = false;
      this.vimeoCallbacks = [];
    }
  
    loadYouTubeAPI() {
      if (this.youtubeReady) return Promise.resolve();
      if (this.youtubeLoading) {
        return new Promise(resolve => this.youtubeCallbacks.push(resolve));
      }
  
      this.youtubeLoading = true;
  
      return new Promise((resolve) => {
        window.onYouTubeIframeAPIReady = () => {
          this.youtubeReady = true;
          this.youtubeLoading = false;
          resolve();
          this.youtubeCallbacks.forEach(cb => cb());
          this.youtubeCallbacks.length = 0;
        };
  
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);
      });
    }
  
    loadVimeoAPI() {
      if (this.vimeoReady) return Promise.resolve();
      if (this.vimeoLoading) {
        return new Promise(resolve => this.vimeoCallbacks.push(resolve));
      }
  
      this.vimeoLoading = true;
  
      return new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = 'https://player.vimeo.com/api/player.js';
        script.onload = () => {
          this.vimeoReady = true;
          this.vimeoLoading = false;
          resolve();
          this.vimeoCallbacks.forEach(cb => cb());
          this.vimeoCallbacks.length = 0;
        };
        document.head.appendChild(script);
      });
    }
  }
  
  // Singleton instance
  const apiLoader = new APILoader();
  
  export class DialogVideo {
    constructor(button, dialogElement) {
      if (!button) {
        throw new Error('DialogVideo requires a button element');
      }
  
      this.button = button;
      this.videoType = button.dataset.videoType;
      this.videoId = button.dataset.videoId || '';
      this.videoUrl = button.dataset.videoUrl || '';
      this.autoplay = button.dataset.autoplay === 'true';
  
      // If dialogElement not provided, find it by data attribute
      if (dialogElement) {
        this.dialogElement = dialogElement;
      } else {
        const dialogId = button.getAttribute('commandfor');
        if (dialogId) {
          this.dialogElement = document.querySelector(`el-dialog[data-video-dialog="${this.videoType}"]`) ||
                               document.querySelector(`#${dialogId}`)?.closest('el-dialog');
        }
      }
  
      if (!this.dialogElement) {
        console.error('DialogVideo: Could not find associated el-dialog element');
        return;
      }
  
      this.playerContainer = this.dialogElement.querySelector('.video-player');
      this.titleElement = this.dialogElement.querySelector('h3');
  
      this.player = null;
      this.isPlayerReady = false;
  
      // Wait for Elements to be ready before initializing
      if (customElements.get('el-dialog')) {
        this.init();
      } else {
        window.addEventListener('elements:ready', () => this.init(), { once: true });
      }
    }
  
    init() {
      if (!this.dialogElement) {
        console.error('DialogVideo: el-dialog element not found');
        return;
      }
  
      // Listen for dialog open/close events
      // Store handlers so we can remove them on destroy (prevents duplicate listeners)
      this._handleOpen = () => this.onDialogOpen();
      this._handleClose = () => this.onDialogClose();

      this.dialogElement.addEventListener('open', this._handleOpen);
      this.dialogElement.addEventListener('close', this._handleClose);
    }
  
    async onDialogOpen() {
      // Lazy load player when dialog opens
      if (!this.player && this.playerContainer) {
        await this.initPlayer();
  
        // Autoplay if enabled
        if (this.autoplay && this.isPlayerReady) {
          setTimeout(() => {
            this.play();
          }, 500); // Small delay to ensure player is fully ready
        }
      }
    }
  
    onDialogClose() {
      // Stop video when dialog closes
      this.stop();
  
      // For extra safety, add a delay and check again
      setTimeout(() => {
        this.stop();
      }, 100);
    }
  
    async initPlayer() {
      if (!this.playerContainer) return;
  
      // Generate unique ID for player container
      const playerId = `video-player-${Date.now()}`;
      this.playerContainer.id = playerId;
  
      try {
        if (this.videoType === 'youtube' && this.videoId) {
          await this.initYouTubePlayer(playerId);
        } else if (this.videoType === 'vimeo' && this.videoId) {
          await this.initVimeoPlayer();
        } else if (this.videoType === 'html5' && this.videoUrl) {
          await this.initHTML5Player();
        }
      } catch (error) {
        console.error('DialogVideo: Error initializing player', error);
      }
    }
  
    async initYouTubePlayer(playerId) {
      await apiLoader.loadYouTubeAPI();
  
      return new Promise((resolve) => {
        this.player = new window.YT.Player(playerId, {
          videoId: this.videoId,
          width: '100%',
          height: '100%',
          playerVars: {
            autoplay: this.autoplay ? 1 : 0,
            rel: 0,
            modestbranding: 1,
            enablejsapi: 1
          },
          events: {
            onReady: (event) => {
              this.isPlayerReady = true;
              // Ensure iframe is keyboard accessible
              const iframe = event.target.getIframe();
              if (iframe) {
                iframe.setAttribute('tabindex', '0');
                iframe.setAttribute('title', 'YouTube video player');
              }
              resolve();
            }
          }
        });
      });
    }
  
    async initVimeoPlayer() {
      await apiLoader.loadVimeoAPI();
  
      const autoplayParam = this.autoplay ? 1 : 0;
      const iframe = document.createElement('iframe');
      iframe.src = `https://player.vimeo.com/video/${this.videoId}?autoplay=${autoplayParam}`;
      iframe.width = '100%';
      iframe.height = '100%';
      iframe.allow = 'autoplay; fullscreen; picture-in-picture';
      iframe.allowFullscreen = true;
      iframe.style.border = 'none';
      iframe.setAttribute('tabindex', '0');
      iframe.setAttribute('title', 'Vimeo video player');
  
      this.playerContainer.appendChild(iframe);
  
      this.player = new window.Vimeo.Player(iframe);
      this.isPlayerReady = true;
    }
  
    async initHTML5Player() {
      const video = document.createElement('video');
      video.controls = true;
      video.className = 'w-full h-full';
      video.preload = 'metadata';
  
      if (this.autoplay) {
        video.autoplay = true;
        video.muted = true; // Required for autoplay in most browsers
      }
  
      const source = document.createElement('source');
      source.src = this.videoUrl;
      source.type = 'video/mp4';
  
      video.appendChild(source);
      this.playerContainer.appendChild(video);
  
      this.player = video;
      this.isPlayerReady = true;
    }
  
    play() {
      if (!this.player || !this.isPlayerReady) return;
  
      try {
        if (this.videoType === 'youtube' && this.player.playVideo) {
          this.player.playVideo();
        } else if (this.videoType === 'vimeo' && this.player.play) {
          this.player.play();
        } else if (this.videoType === 'html5' && this.player.play) {
          this.player.play();
        }
      } catch (error) {
        console.error('DialogVideo: Error playing video', error);
      }
    }
  
    pause() {
      if (!this.player || !this.isPlayerReady) return;
  
      try {
        if (this.videoType === 'youtube' && this.player.pauseVideo) {
          this.player.pauseVideo();
        } else if (this.videoType === 'vimeo' && this.player.pause) {
          this.player.pause();
        } else if (this.videoType === 'html5' && this.player.pause) {
          this.player.pause();
        }
      } catch (error) {
        console.error('DialogVideo: Error pausing video', error);
      }
    }
  
    stop() {
      if (!this.player) return;
  
      try {
        if (this.videoType === 'youtube') {
          // Check if methods exist before calling
          if (typeof this.player.pauseVideo === 'function') {
            this.player.pauseVideo();
          }
          if (typeof this.player.seekTo === 'function') {
            this.player.seekTo(0);
          }
          if (typeof this.player.stopVideo === 'function') {
            this.player.stopVideo();
          }
        } else if (this.videoType === 'vimeo') {
          if (typeof this.player.pause === 'function') {
            this.player.pause().catch(() => {});
          }
          if (typeof this.player.setCurrentTime === 'function') {
            this.player.setCurrentTime(0).catch(() => {});
          }
        } else if (this.videoType === 'html5') {
          if (this.player.pause) {
            this.player.pause();
          }
          this.player.currentTime = 0;
        }
      } catch (error) {
        console.error('DialogVideo: Error stopping video', error);
      }
    }
  
    setVideoId(videoId) {
      this.videoId = videoId;
      this.button.dataset.videoId = videoId;
  
      // Reset player if it exists
      if (this.player) {
        this.destroy();
      }
    }
  
    setVideoUrl(videoUrl) {
      this.videoUrl = videoUrl;
      this.button.dataset.videoUrl = videoUrl;
  
      // Reset player if it exists
      if (this.player) {
        this.destroy();
      }
    }
  
    setTitle(title) {
      if (this.titleElement) {
        this.titleElement.textContent = title;
      }
    }
  
    destroy() {
      this.stop();
  
      // Remove event listeners
      if (this.dialogElement) {
        if (this._handleOpen) this.dialogElement.removeEventListener('open', this._handleOpen);
        if (this._handleClose) this.dialogElement.removeEventListener('close', this._handleClose);
      }

      if (this.player) {
        try {
          if (this.videoType === 'youtube' && this.player.destroy) {
            this.player.destroy();
          } else if (this.videoType === 'vimeo' && this.player.destroy) {
            this.player.destroy();
          }
        } catch (error) {
          console.error('DialogVideo: Error destroying player', error);
        }
      }
  
      this.player = null;
      this.isPlayerReady = false;
      this._handleOpen = null;
      this._handleClose = null;
    }
  }
  
  // Auto-initialize all video dialogs on the page
  export function initAllVideoDialogs() {
    const initFn = () => {
      const buttons = document.querySelectorAll('button[data-video-type]');
      const instances = [];
  
      buttons.forEach(button => {
        try {
          // Idempotent per button
          if (button._dialogVideoInstance) {
            instances.push(button._dialogVideoInstance);
            return;
          }
          const instance = new DialogVideo(button);
          button._dialogVideoInstance = instance;
          instances.push(instance);
        } catch (error) {
          console.error('Failed to initialize DialogVideo:', error);
        }
      });
  
      return instances;
    };
  
    // Wait for Elements to be ready
    if (customElements.get('el-dialog')) {
      return initFn();
    } else {
      return new Promise((resolve) => {
        window.addEventListener('elements:ready', () => {
          resolve(initFn());
        }, { once: true });
      });
    }
  }
  