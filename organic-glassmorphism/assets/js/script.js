/**
 * Organic Glassmorphism - Dark/Normal Mode Toggle
 *
 * This script handles the logic for switching between light and dark themes,
 * saving the user's preference in localStorage, and respecting OS-level settings.
 *
 * @since 1.0.0
 */
document.addEventListener('DOMContentLoaded', () => {
	const themeToggle = document.getElementById('og-theme-toggle-checkbox');
	const bodyEl = document.body;

	if (!themeToggle) {
		return;
	}

	const applyTheme = (theme) => {
		if (theme === 'dark') {
			bodyEl.classList.add('dark-mode');
			themeToggle.checked = true;
		} else {
			bodyEl.classList.remove('dark-mode');
			themeToggle.checked = false;
		}
	};

	const getInitialTheme = () => {
		const savedTheme = localStorage.getItem('og-theme');
		if (savedTheme) {
			return savedTheme;
		}
		if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
			return 'dark';
		}
		return 'light'; // Default theme
	};

	// Set the initial theme on page load
	const currentTheme = getInitialTheme();
	applyTheme(currentTheme);

	// Listener for the toggle switch
	themeToggle.addEventListener('change', function () {
		const newTheme = this.checked ? 'dark' : 'light';
		applyTheme(newTheme);
		localStorage.setItem('og-theme', newTheme);
	});

	// Listener for changes in OS preference
	window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
		// Only apply OS preference if the user hasn't made an explicit choice.
		if (!localStorage.getItem('og-theme')) {
			applyTheme(e.matches ? 'dark' : 'light');
		}
	});
});
