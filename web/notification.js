const toastContainer = document.getElementById('toast-container');
let lastMessage = null; // Track last shown message

    /**
     * Creates a toast element with given message and returns it.
     * @param {string} message - The text content of the toast.
     * @returns {HTMLElement} The toast div element.
     */
    function createToast(message,bgcolor,color) {
      const toast = document.createElement('div');
      toast.style.fontWeight="bold";
      toast.style.color = color;
      toast.style.backgroundColor=bgcolor;
      toast.classList.add('toast');
      toast.textContent = message;
      return toast;
    }

    /**
     * Shows a toast notification with animation and auto-dismiss after 3 seconds.
     * Toasts stack upwards in the container.
     * @param {string} message - The message to display in the toast.
     */
    function showToast(message,bgcolor = "rgba(13, 134, 0, 0.7)",color="white") {
      if (message === lastMessage) {
      // Skip duplicate message
      return;
      }
      lastMessage = message;
      const toast = createToast(message,bgcolor,color);

      // Add toast to container at the end (bottom)
      toastContainer.appendChild(toast);

      // Force reflow to enable transition on adding 'show' class
      void toast.offsetWidth;

      // Show the toast (fade in and slide up)
      toast.classList.add('show');

      // After 3 seconds, hide the toast (fade out and slide down)
      setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');

        // After transition ends, remove toast from DOM
        toast.addEventListener('transitionend', () => {
          toast.remove();
        }, { once: true });
      }, 3000);
    }