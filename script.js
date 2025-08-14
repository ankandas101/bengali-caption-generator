document.addEventListener('DOMContentLoaded', () => {
    const keywordInput = document.getElementById('keyword');
    const generateBtn = document.getElementById('generateBtn');
    const loadingDiv = document.getElementById('loading');
    const resultDiv = document.getElementById('result');
    const captionsContainer = document.getElementById('captionsContainer');
    const copyBtn = document.getElementById('copyBtn');
    const keywordButtons = document.querySelectorAll('.keyword-btn');
    const selectedKeywordsList = document.getElementById('selectedKeywordsList');
    const generateMoreBtn = document.getElementById('generateMoreBtn');
    const historyContainer = document.getElementById('historyContainer');
    const favoritesContainer = document.getElementById('favoritesContainer');
    const historyTab = document.getElementById('historyTab');
    const favoritesTab = document.getElementById('favoritesTab');

    // Set to store selected keywords
    const selectedKeywords = new Set();
    let currentKeywords = '';
    let generatedCaptions = [];
    
    // Initialize history and favorites
    let captionHistory = [];
    let favoriteCaptions = [];
    
    // Check if user is logged in (variable passed from PHP)
    const useDatabase = typeof isLoggedIn !== 'undefined' && isLoggedIn;
    
    // Load history and favorites based on login status
    if (useDatabase) {
        // Load from database via AJAX
        Promise.all([
            fetch('history_handler.php?action=get').then(response => response.json()),
            fetch('favorites_handler.php?action=get').then(response => response.json())
        ])
        .then(([historyData, favoritesData]) => {
            if (historyData.success && Array.isArray(historyData.history)) {
                captionHistory = historyData.history;
                if (historyTab && historyTab.classList.contains('active')) {
                    displayHistory();
                }
            }
            
            if (favoritesData.success && Array.isArray(favoritesData.favorites)) {
                favoriteCaptions = favoritesData.favorites;
                if (favoritesTab && favoritesTab.classList.contains('active')) {
                    displayFavorites();
                }
            }
        })
        .catch(error => {
            console.error('Error loading data from database:', error);
        });
    } else {
        // Load from localStorage for non-logged in users
        captionHistory = JSON.parse(localStorage.getItem('captionHistory')) || [];
        favoriteCaptions = JSON.parse(localStorage.getItem('favoriteCaptions')) || [];
    }

    // Function to update selected keywords display
    function updateSelectedKeywordsDisplay() {
        if (!selectedKeywordsList) return;
        
        selectedKeywordsList.innerHTML = '';
        selectedKeywords.forEach(keyword => {
            const tag = document.createElement('div');
            tag.className = 'selected-keyword-tag';
            tag.innerHTML = `
                ${keyword}
                <button class="remove-btn" data-keyword="${keyword}">&times;</button>
            `;
            selectedKeywordsList.appendChild(tag);
        });
    }

    // Function to display captions with animations
    function displayCaptions() {
        if (!captionsContainer) return;
        
        captionsContainer.innerHTML = '';
        generatedCaptions.forEach((caption, index) => {
            const captionDiv = document.createElement('div');
            captionDiv.className = 'caption-item';
            captionDiv.style.opacity = '0';
            captionDiv.style.transform = 'translateY(20px)';
            captionDiv.innerHTML = `
                <p class="caption-text">${caption}</p>
                <div class="caption-actions">
                    <button class="favorite-btn ${isFavorite(caption) ? 'favorite-active' : ''}" data-index="${index}" title="${isFavorite(caption) ? 'Remove from favorites' : 'Add to favorites'}">
                        <span class="favorite-icon">${isFavorite(caption) ? '★' : '☆'}</span>
                    </button>
                    <button class="copy-btn" data-index="${index}" title="Copy to clipboard">
                        <span class="copy-text">Copy</span>
                        <span class="copy-success" style="display: none;">✓</span>
                    </button>
                </div>
            `;
            captionsContainer.appendChild(captionDiv);
            
            // Animate in
            setTimeout(() => {
                captionDiv.style.transition = 'all 0.4s ease-out';
                captionDiv.style.opacity = '1';
                captionDiv.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Scroll to the last caption if it's a "Generate More" action
        if (generatedCaptions.length > 4) {
            setTimeout(() => {
                const lastCaption = captionsContainer.lastElementChild;
                if (lastCaption) {
                    lastCaption.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }
            }, 300);
        }
    }

    // Function to handle caption generation
    async function generateCaptions(isGenerateMore = false) {
        if (!keywordInput || !loadingDiv || !resultDiv || !generateBtn) return;

        const customKeyword = keywordInput.value.trim();
        let allKeywords = Array.from(selectedKeywords);
        
        if (customKeyword) {
            allKeywords.push(customKeyword);
        }
        
        if (allKeywords.length === 0) {
            alert('Please select or enter at least one keyword');
            return;
        }

        currentKeywords = allKeywords.join(', ');

        // Show loading state
        loadingDiv.classList.remove('hidden');
        resultDiv.classList.add('hidden');
        generateBtn.disabled = true;
        if (generateMoreBtn) generateMoreBtn.disabled = true;

        try {
            const response = await fetch('generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ keyword: currentKeywords })
            });

            const data = await response.json();
            console.log('API Response:', data);

            if (data.error) {
                throw new Error(data.error);
            }

            if (!data.captions || !Array.isArray(data.captions)) {
                throw new Error('No captions received from the API');
            }

            // Add the new captions to our array
            if (isGenerateMore) {
                generatedCaptions = [...generatedCaptions, ...data.captions];
            } else {
                generatedCaptions = data.captions;
            }
            
            // Save to history
            saveToHistory(currentKeywords, data.captions);
            
            // Display all captions
            displayCaptions();
            loadingDiv.classList.add('hidden');
            resultDiv.classList.remove('hidden');
            if (generateMoreBtn) generateMoreBtn.classList.remove('hidden');
            
            // Update history display if visible
            if (historyContainer && !historyContainer.classList.contains('hidden')) {
                displayHistory();
            }

            // Scroll to the new captions
            if (isGenerateMore && captionsContainer) {
                captionsContainer.scrollTo({
                    top: captionsContainer.scrollHeight,
                    behavior: 'smooth'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error generating captions: ' + error.message);
            loadingDiv.classList.add('hidden');
        } finally {
            generateBtn.disabled = false;
            if (generateMoreBtn) generateMoreBtn.disabled = false;
        }
    }

    // Handle keyword button clicks
    keywordButtons.forEach(button => {
        button.addEventListener('click', () => {
            const keyword = button.dataset.keyword;
            if (selectedKeywords.has(keyword)) {
                selectedKeywords.delete(keyword);
                button.classList.remove('selected');
            } else {
                selectedKeywords.add(keyword);
                button.classList.add('selected');
            }
            updateSelectedKeywordsDisplay();
        });
    });

    // Handle removing keywords from selected list
    if (selectedKeywordsList) {
        selectedKeywordsList.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-btn')) {
                const keyword = e.target.dataset.keyword;
                selectedKeywords.delete(keyword);
                const button = document.querySelector(`.keyword-btn[data-keyword="${keyword}"]`);
                if (button) {
                    button.classList.remove('selected');
                }
                updateSelectedKeywordsDisplay();
            }
        });
    }

    // Add custom keyword when Enter is pressed
    if (keywordInput) {
        keywordInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const customKeyword = keywordInput.value.trim();
                if (customKeyword) {
                    selectedKeywords.add(customKeyword);
                    updateSelectedKeywordsDisplay();
                    keywordInput.value = '';
                }
            }
        });
    }

    // Generate button click handler
    if (generateBtn) {
        generateBtn.addEventListener('click', () => {
            generateCaptions(false);
        });
    }

    // Generate More button click handler
    if (generateMoreBtn) {
        generateMoreBtn.addEventListener('click', () => {
            generateCaptions(true);
        });
    }

    // Handle copy button clicks for individual captions
    if (captionsContainer) {
        captionsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('copy-btn')) {
                const index = e.target.dataset.index;
                const captionText = e.target.closest('.caption-item').querySelector('.caption-text').textContent;
                navigator.clipboard.writeText(captionText).then(() => {
                    const originalText = e.target.textContent;
                    e.target.textContent = 'Copied!';
                    setTimeout(() => {
                        e.target.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            } else if (e.target.classList.contains('favorite-btn')) {
                const index = e.target.dataset.index;
                const captionText = e.target.closest('.caption-item').querySelector('.caption-text').textContent;
                toggleFavorite(captionText, e.target);
            }
        });
    }
    
    // Function to check if a caption is in favorites
    function isFavorite(caption) {
        return favoriteCaptions.some(fav => {
            // Handle different data types for comparison
            const favText = typeof fav === 'string' ? fav : 
                           Array.isArray(fav) ? fav.join(' ') :
                           typeof fav === 'object' && fav !== null ? (fav.caption || fav.text || JSON.stringify(fav)) :
                           String(fav);
            
            const captionText = typeof caption === 'string' ? caption :
                               Array.isArray(caption) ? caption.join(' ') :
                               typeof caption === 'object' && caption !== null ? (caption.caption || caption.text || JSON.stringify(caption)) :
                               String(caption);
            
            return favText === captionText;
        });
    }
    
    // Function to toggle favorite status
    function toggleFavorite(caption, button) {
        const isFav = isFavorite(caption);
        
        if (isFav) {
            // Remove from favorites
            favoriteCaptions = favoriteCaptions.filter(fav => {
                const favText = typeof fav === 'string' ? fav : 
                               Array.isArray(fav) ? fav.join(' ') :
                               typeof fav === 'object' && fav !== null ? (fav.caption || fav.text || JSON.stringify(fav)) :
                               String(fav);
                
                const captionText = typeof caption === 'string' ? caption :
                                   Array.isArray(caption) ? caption.join(' ') :
                                   typeof caption === 'object' && caption !== null ? (caption.caption || caption.text || JSON.stringify(caption)) :
                                   String(caption);
                
                return favText !== captionText;
            });
            button.textContent = '☆';
        } else {
            // Add to favorites
            favoriteCaptions.push(caption);
            button.textContent = '★';
        }
        
        if (useDatabase) {
            // Save to database via AJAX
            fetch('favorites_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: isFav ? 'remove' : 'add',
                    caption: caption
                })
            }).catch(error => {
                console.error('Error updating favorites in database:', error);
            });
        } else {
            // Save to localStorage for non-logged in users
            localStorage.setItem('favoriteCaptions', JSON.stringify(favoriteCaptions));
        }
        
        // Update favorites display if visible
        if (favoritesContainer && !favoritesContainer.classList.contains('hidden')) {
            displayFavorites();
        }
    }
    
    // Function to save captions to history
    function saveToHistory(keywords, captions) {
        const timestamp = new Date().toLocaleString();
        const historyEntry = {
            keywords,
            captions,
            timestamp
        };
        
        // Add to beginning of history array
        captionHistory.unshift(historyEntry);
        
        // Limit history to 50 entries
        if (captionHistory.length > 50) {
            captionHistory = captionHistory.slice(0, 50);
        }
        
        if (useDatabase) {
            // Save to database via AJAX
            fetch('history_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save',
                    keywords: keywords,
                    captions: captions
                })
            }).catch(error => {
                console.error('Error saving history to database:', error);
            });
        } else {
            // Save to localStorage for non-logged in users
            localStorage.setItem('captionHistory', JSON.stringify(captionHistory));
        }
    }
    
    // Function to display history
    function displayHistory() {
        if (!historyContainer) return;
        
        historyContainer.innerHTML = '';
        
        if (captionHistory.length === 0) {
            historyContainer.innerHTML = '<p class="empty-message">No history yet</p>';
            return;
        }
        
        captionHistory.forEach((entry, entryIndex) => {
            const historyEntry = document.createElement('div');
            historyEntry.className = 'history-entry';
            
            const header = document.createElement('div');
            header.className = 'history-header';
            header.innerHTML = `
                <div class="history-info">
                    <span class="history-keywords">${entry.keywords}</span>
                    <span class="history-timestamp">${entry.timestamp}</span>
                </div>
                <button class="history-toggle">Show</button>
            `;
            
            const captionsList = document.createElement('div');
            captionsList.className = 'history-captions hidden';
            
            entry.captions.forEach((caption, captionIndex) => {
                // Handle different data types for display
                let captionText = '';
                if (typeof caption === 'string') {
                    captionText = caption;
                } else if (Array.isArray(caption)) {
                    captionText = caption.join(' ');
                } else if (typeof caption === 'object' && caption !== null) {
                    captionText = caption.caption || caption.text || JSON.stringify(caption);
                } else {
                    captionText = String(caption);
                }
                
                const captionItem = document.createElement('div');
                captionItem.className = 'caption-item';
                captionItem.innerHTML = `
                    <p class="caption-text">${captionText}</p>
                    <div class="caption-actions">
                        <button class="favorite-btn" data-entry="${entryIndex}" data-caption="${captionIndex}">
                            ${isFavorite(caption) ? '★' : '☆'}
                        </button>
                        <button class="copy-btn" data-entry="${entryIndex}" data-caption="${captionIndex}">Copy</button>
                    </div>
                `;
                captionsList.appendChild(captionItem);
            });
            
            historyEntry.appendChild(header);
            historyEntry.appendChild(captionsList);
            historyContainer.appendChild(historyEntry);
        });
    }
    
    // Function to display favorites
    function displayFavorites() {
        if (!favoritesContainer) return;
        
        favoritesContainer.innerHTML = '';
        
        if (favoriteCaptions.length === 0) {
            favoritesContainer.innerHTML = '<p class="empty-message">No favorites yet</p>';
            return;
        }
        
        favoriteCaptions.forEach((caption, index) => {
            // Handle different data types (string, array, object)
            let captionText = '';
            if (typeof caption === 'string') {
                captionText = caption;
            } else if (Array.isArray(caption)) {
                captionText = caption.join(' ');
            } else if (typeof caption === 'object' && caption !== null) {
                captionText = caption.caption || caption.text || JSON.stringify(caption);
            } else {
                captionText = String(caption);
            }
            
            const favoriteItem = document.createElement('div');
            favoriteItem.className = 'caption-item';
            favoriteItem.innerHTML = `
                <p class="caption-text">${captionText}</p>
                <div class="caption-actions">
                    <button class="favorite-btn favorite-active" data-favorite-index="${index}">★</button>
                    <button class="copy-btn" data-favorite-index="${index}">Copy</button>
                </div>
            `;
            favoritesContainer.appendChild(favoriteItem);
        });
    }
    
    // Handle tab switching
    if (historyTab && favoritesTab && document.body.id !== 'user-dashboard') {
        historyTab.addEventListener('click', () => {
            historyTab.classList.add('active');
            favoritesTab.classList.remove('active');
            historyContainer.classList.remove('hidden');
            favoritesContainer.classList.add('hidden');
            displayHistory();
        });
        favoritesTab.addEventListener('click', () => {
            favoritesTab.classList.add('active');
            historyTab.classList.remove('active');
            favoritesContainer.classList.remove('hidden');
            historyContainer.classList.add('hidden');
            displayFavorites();
        });
    }
    
    // Handle history container events
    if (historyContainer) {
        historyContainer.addEventListener('click', (e) => {
            // Toggle show/hide captions
            if (e.target.classList.contains('history-toggle')) {
                const captionsList = e.target.closest('.history-entry').querySelector('.history-captions');
                captionsList.classList.toggle('hidden');
                e.target.textContent = captionsList.classList.contains('hidden') ? 'Show' : 'Hide';
            }
            
            // Copy caption from history
            if (e.target.classList.contains('copy-btn')) {
                const entryIndex = e.target.dataset.entry;
                const captionIndex = e.target.dataset.caption;
                const caption = captionHistory[entryIndex].captions[captionIndex];
                
                // Handle different data types for copying
                let captionText = '';
                if (typeof caption === 'string') {
                    captionText = caption;
                } else if (Array.isArray(caption)) {
                    captionText = caption.join(' ');
                } else if (typeof caption === 'object' && caption !== null) {
                    captionText = caption.caption || caption.text || JSON.stringify(caption);
                } else {
                    captionText = String(caption);
                }
                
                navigator.clipboard.writeText(captionText).then(() => {
                    const originalText = e.target.textContent;
                    e.target.textContent = 'Copied!';
                    setTimeout(() => {
                        e.target.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
            
            // Toggle favorite from history
            if (e.target.classList.contains('favorite-btn')) {
                const entryIndex = e.target.dataset.entry;
                const captionIndex = e.target.dataset.caption;
                const captionText = captionHistory[entryIndex].captions[captionIndex];
                
                toggleFavorite(captionText, e.target);
            }
        });
    }
    
    // Handle favorites container events
    if (favoritesContainer) {
        favoritesContainer.addEventListener('click', (e) => {
            // Copy caption from favorites
            if (e.target.classList.contains('copy-btn')) {
                const index = e.target.dataset.favoriteIndex;
                const caption = favoriteCaptions[index];
                
                // Handle different data types for copying
                let captionText = '';
                if (typeof caption === 'string') {
                    captionText = caption;
                } else if (Array.isArray(caption)) {
                    captionText = caption.join(' ');
                } else if (typeof caption === 'object' && caption !== null) {
                    captionText = caption.caption || caption.text || JSON.stringify(caption);
                } else {
                    captionText = String(caption);
                }
                
                navigator.clipboard.writeText(captionText).then(() => {
                    const originalText = e.target.textContent;
                    e.target.textContent = 'Copied!';
                    setTimeout(() => {
                        e.target.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
            
            // Remove from favorites
            if (e.target.classList.contains('favorite-btn')) {
                const index = e.target.dataset.favoriteIndex;
                favoriteCaptions.splice(index, 1);
                localStorage.setItem('favoriteCaptions', JSON.stringify(favoriteCaptions));
                displayFavorites();
            }
        });
    }
    
    // Initialize tabs if they exist
    if (historyTab && historyTab.classList.contains('active')) {
        displayHistory();
    } else if (favoritesTab && favoritesTab.classList.contains('active')) {
        displayFavorites();
    }
});