/**
 * @fileoverview Debug utility for conditional console logging
 * @module utils/debug
 * 
 * Provides a centralized debug flag system that can be controlled via:
 * - URL parameter: ?debug=true
 * - localStorage: localStorage.setItem('debug', 'true')
 * - Environment: window.DEBUG = true
 * 
 * @example
 * import { debugLog, debugWarn, debugError } from './utils/debug.js';
 * 
 * debugLog('Component initialized');
 * debugWarn('CSS variables not ready yet');
 * debugError('Failed to load', error);
 */

/**
 * Check if debug mode is enabled
 * Checks in order: URL param > localStorage > window.DEBUG
 * @returns {boolean} True if debug mode is enabled
 */
function isDebugEnabled() {
  // Check URL parameter
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('debug') === 'true') {
    return true;
  }
  
  // Check localStorage
  if (localStorage.getItem('debug') === 'true') {
    return true;
  }
  
  // Check window.DEBUG
  if (window.DEBUG === true) {
    return true;
  }
  
  return false;
}

/**
 * Debug-aware console.log wrapper
 * @param {...any} args - Arguments to log
 */
export function debugLog(...args) {
  if (isDebugEnabled()) {
    console.log(...args);
  }
}

/**
 * Debug-aware console.warn wrapper
 * @param {...any} args - Arguments to warn
 */
export function debugWarn(...args) {
  if (isDebugEnabled()) {
    console.warn(...args);
  }
}

/**
 * Debug-aware console.error wrapper
 * @param {...any} args - Arguments to error
 */
export function debugError(...args) {
  if (isDebugEnabled()) {
    console.error(...args);
  }
}

/**
 * Debug-aware console.info wrapper
 * @param {...any} args - Arguments to info
 */
export function debugInfo(...args) {
  if (isDebugEnabled()) {
    console.info(...args);
  }
}

/**
 * Get current debug state
 * @returns {boolean} Current debug state
 */
export function getDebugState() {
  return isDebugEnabled();
}

/**
 * Enable debug mode programmatically
 */
export function enableDebug() {
  localStorage.setItem('debug', 'true');
}

/**
 * Disable debug mode programmatically
 */
export function disableDebug() {
  localStorage.removeItem('debug');
}

