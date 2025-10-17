// Motion on load for main content and cards
document.addEventListener('DOMContentLoaded', () => {
  const main = document.querySelector('main');
  if (main) main.classList.add('animate-fadeInUp');
  document.querySelectorAll('.glass-card').forEach((el, idx) => {
    el.style.animationDelay = `${Math.min(idx * 60, 360)}ms`;
    el.classList.add('animate-fadeInUp');
  });
});

// Lightweight helper to POST to chatbot endpoint
async function postChatbot(query) {
  try {
    const response = await fetch('chatbot.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ query })
    });
    const data = await response.json();
    if (data && typeof data.response === 'string') return data.response;
    return 'No response from chatbot.';
  } catch (e) {
    return 'Chatbot request failed.';
  }
}

// Expose globally for Alpine inline usage
window.postChatbot = postChatbot;

