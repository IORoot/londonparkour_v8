/**
 * SVG Icon Helper
 * 
 * Creates SVG icons using symbol references from #svg-icons.
 * Uses fill="currentColor" and text-primary class for theme-aware coloring.
 * 
 * @example
 * import { createIcon } from '@assets/utils/svgHelper.js';
 * 
 * // Using icon ID (recommended)
 * const icon = createIcon({
 *   iconId: 'icon-academic-cap',
 *   className: 'size-5 flex-none text-primary'
 * });
 * 
 * // Using path data (legacy support)
 * const icon = createIcon({
 *   viewBox: '0 0 20 20',
 *   path: 'M5.5 17a4.5 4.5 0 0 1-1.44-8.765...',
 *   className: 'size-5 flex-none text-primary'
 * });
 */

/**
 * Create an SVG icon element
 * @param {Object} options - Icon configuration
 * @param {string} options.iconId - Icon ID from #svg-icons (e.g., 'icon-academic-cap')
 * @param {string} options.viewBox - SVG viewBox attribute (e.g., '0 0 20 20') - only used if iconId not provided
 * @param {string|Array<string>} options.path - SVG path(s) as string or array of path strings - only used if iconId not provided
 * @param {string} options.className - CSS classes (default: 'size-5 flex-none text-primary')
 * @param {string} options.fill - Fill attribute (default: 'currentColor')
 * @param {boolean} options.ariaHidden - Whether to add aria-hidden (default: true)
 * @param {string} options.dataSlot - data-slot attribute (default: 'icon')
 * @returns {string} SVG HTML string
 */
import { esc } from './html.js';

export function createIcon({
  iconId = null,
  viewBox = '0 0 20 20',
  path = '',
  className = 'size-5 flex-none text-primary',
  fill = 'currentColor',
  ariaHidden = true,
  dataSlot = 'icon'
} = {}) {
  // If iconId is provided, use <use> element to reference the symbol
  if (iconId) {
    const ariaAttrs = ariaHidden ? 'aria-hidden="true"' : '';
    const dataSlotAttr = dataSlot ? `data-slot="${dataSlot}"` : '';
    
    // Use the symbol from #svg-icons. `iconId` is escaped at this single
    // shared point rather than in each caller: components are ported into
    // consumer sites where an icon id can be data-driven (a CMS category
    // mapped to an icon), and every caller routes through here.
    return `<svg ${dataSlotAttr} ${ariaAttrs} class="${className}"><use href="#${esc(iconId)}"></use></svg>`;
  }
  
  // Legacy support: build from path data
  const paths = Array.isArray(path) ? path : [path];
  
  // Build path elements
  const pathElements = paths
    .filter(p => p && p.trim())
    .map(p => {
      // Extract attributes if path is already an element string
      if (p.includes('<path')) {
        // Extract path data and attributes from existing path element
        const pathMatch = p.match(/<path[^>]*d=["']([^"']+)["'][^>]*>/);
        const existingClipRule = p.match(/clip-rule=["']([^"']+)["']/);
        const existingFillRule = p.match(/fill-rule=["']([^"']+)["']/);
        
        const pathData = pathMatch ? pathMatch[1] : '';
        const clipRule = existingClipRule ? ` clip-rule="${existingClipRule[1]}"` : '';
        const fillRule = existingFillRule ? ` fill-rule="${existingFillRule[1]}"` : '';
        
        return `<path d="${pathData}" fill="${fill}"${clipRule}${fillRule} />`;
      }
      // Simple path data string
      return `<path d="${p}" fill="${fill}" />`;
    })
    .join('');

  // Build aria attributes
  const ariaAttrs = ariaHidden ? 'aria-hidden="true"' : '';
  const dataSlotAttr = dataSlot ? `data-slot="${dataSlot}"` : '';

  // Build SVG element
  return `<svg viewBox="${viewBox}" ${dataSlotAttr} ${ariaAttrs} class="${className}">${pathElements}</svg>`;
}

/**
 * Create an icon from a path data string (convenience function)
 * @param {string} pathData - SVG path data
 * @param {Object} options - Additional options (viewBox, className, etc.)
 * @returns {string} SVG HTML string
 */
export function createIconFromPath(pathData, options = {}) {
  return createIcon({
    path: pathData,
    ...options
  });
}

/**
 * Predefined icon paths for common icons
 * These can be used with createIcon or extended
 */
export const iconPaths = {
  // Add common icon paths here as needed
  // Example:
  // cloud: 'M5.5 17a4.5 4.5 0 0 1-1.44-8.765...',
};

export default {
  createIcon,
  createIconFromPath,
  iconPaths
};

