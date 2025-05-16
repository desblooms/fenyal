 // network-status.js
   export function initNetworkMonitoring() {
     let toastTimeout;
     
     function showToast(message, type) {
       // Clear any existing toast
       if (toastTimeout) {
         clearTimeout(toastTimeout);
       }
       
       // Remove existing toast
       const existingToast = document.getElementById('network-toast');
       if (existingToast) {
         existingToast.remove();
       }
       
       // Create new toast
       const toast = document.createElement('div');
       toast.id = 'network-toast';
       toast.className = `fixed top-4 left-0 right-0 flex justify-center items-center z-50 pointer-events-none transition-opacity duration-300 opacity-0`;
       
       const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
       
       toast.innerHTML = `
         <div class="${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center max-w-xs">
           <span class="mr-2">
             ${type === 'success' 
               ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
               : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path><path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>'
             }
           </span>
           <span>${message}</span>
         </div>
       `;
       
       document.body.appendChild(toast);
       
       // Animate in
       setTimeout(() => {
         toast.classList.remove('opacity-0');
         toast.classList.add('opacity-100');
       }, 10);
       
       // Hide after delay
       toastTimeout = setTimeout(() => {
         toast.classList.remove('opacity-100');
         toast.classList.add('opacity-0');
         
         setTimeout(() => {
           toast.remove();
         }, 300);
       }, 3000);
     }
     
     // Show initial toast if offline
     if (!navigator.onLine) {
       showToast('You are offline. Some features may be limited.', 'error');
     }
     
     // Listen for online/offline events
     window.addEventListener('online', () => {
       showToast('You are back online', 'success');
     });
     
     window.addEventListener('offline', () => {
       showToast('You are offline. Some features may be limited.', 'error');
     });
   }