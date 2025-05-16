export function initInstallationGuide() {
     // Only show for non-installed app and if not previously dismissed
     const isAppInstalled = window.matchMedia('(display-mode: standalone)').matches || 
                          (window.navigator.standalone === true);
     
     if (isAppInstalled || localStorage.getItem('installGuideDismissed')) {
       return;
     }
     
     // Create the guide after a delay
     setTimeout(() => {
       const guideHTML = `
         <div id="install-guide" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
           <div class="bg-white rounded-xl max-w-sm w-full shadow-2xl">
             <!-- Content will be dynamically updated -->
             <div class="flex items-center justify-between border-b p-4">
               <h3 class="font-semibold text-lg">Install Guide</h3>
               <button id="close-guide" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100">
                 <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
               </button>
             </div>
             <div id="guide-content" class="p-6">
               <!-- Step content will go here -->
             </div>
             <div class="border-t p-4 flex justify-between">
               <button id="prev-step" class="px-4 py-2 text-gray-600 font-medium text-sm rounded hover:bg-gray-100 invisible">
                 Previous
               </button>
               <button id="next-step" class="px-4 py-2 bg-primary text-white font-medium text-sm rounded hover:bg-primary/90">
                 Next
               </button>
             </div>
           </div>
         </div>
       `;
       
       // Append to body
       const guideContainer = document.createElement('div');
       guideContainer.innerHTML = guideHTML;
       document.body.appendChild(guideContainer.firstChild);
       
       // Step data
       const steps = [
         {
           title: "Install Fenyal App",
           description: "Enhance your experience by installing our app on your device. It's free and doesn't require any download from app stores!",
           icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>'
         },
         {
           title: "Works Offline",
           description: "Browse the menu and access your cart even without an internet connection.",
           icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>'
         },
         {
           title: "Faster Access",
           description: "Launch directly from your home screen without opening a browser.",
           icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
         },
         {
           title: "Full-Screen Experience",
           description: "Enjoy a distraction-free, app-like experience without browser controls.",
           icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>'
         },
         {
           title: "How to Install",
           description: /iPhone|iPad|iPod/.test(navigator.userAgent) && !window.MSStream ? 
             "Tap the share icon at the bottom of your browser, then select 'Add to Home Screen'." : 
             "When prompted, click 'Install' or tap the install banner at the top of your browser.",
           icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>'
         }
       ];
       
       let currentStep = 0;
       
       // Function to update step content
       function updateStepContent() {
         const step = steps[currentStep];
         const content = document.getElementById('guide-content');
         const prevBtn = document.getElementById('prev-step');
         const nextBtn = document.getElementById('next-step');
         
         // Update content
         content.innerHTML = `
           <div class="flex flex-col items-center text-center">
             <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-4">
               ${step.icon}
             </div>
             <h4 class="text-lg font-medium mb-2">${step.title}</h4>
             <p class="text-gray-600 mb-6">${step.description}</p>
             
             <!-- Step indicators -->
             <div class="flex space-x-1 mb-6">
               ${steps.map((_, index) => `
                 <div class="w-2 h-2 rounded-full ${currentStep === index ? 'bg-primary' : 'bg-gray-200'}"></div>
               `).join('')}
             </div>
           </div>
         `;
         
         // Update buttons
         prevBtn.classList.toggle('invisible', currentStep === 0);
         nextBtn.textContent = currentStep < steps.length - 1 ? 'Next' : 'Got it';
       }
       
       // Initialize first step
       updateStepContent();
       
       // Add event listeners
       document.getElementById('close-guide').addEventListener('click', () => {
         document.getElementById('install-guide').remove();
         localStorage.setItem('installGuideDismissed', 'true');
       });
       
       document.getElementById('prev-step').addEventListener('click', () => {
         if (currentStep > 0) {
           currentStep--;
           updateStepContent();
         }
       });
       
       document.getElementById('next-step').addEventListener('click', () => {
         if (currentStep < steps.length - 1) {
           currentStep++;
           updateStepContent();
         } else {
           document.getElementById('install-guide').remove();
           localStorage.setItem('installGuideDismissed', 'true');
         }
       });
     }, 5000);
   }