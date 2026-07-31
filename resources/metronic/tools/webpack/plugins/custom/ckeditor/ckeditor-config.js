/**
 * Shared CKEditor 5 config for new ckeditor5 package (v47+).
 * Normalizes legacy toolbar.items and adds licenseKey.
 */
export const LICENSE_KEY = 'GPL';

/**
 * Normalize create() config: toolbar.items -> toolbar array, add licenseKey if missing.
 * @param {object} config - Config passed from page (e.g. toolbar: { items: [...] })
 * @returns {object} Config suitable for ckeditor5 .create()
 */
export function normalizeConfig(config) {
    const out = { ...config, licenseKey: config.licenseKey || LICENSE_KEY };
    // Keep toolbar as object with items for ClassicEditor; do not replace with array
    if (out.toolbar && Array.isArray(out.toolbar)) {
        out.toolbar = { items: out.toolbar, shouldNotGroupWhenFull: true };
    } else if (out.toolbar && !Array.isArray(out.toolbar) && !out.toolbar.items && out.toolbar.removeItems) {
        out.toolbar = { items: [], removeItems: out.toolbar.removeItems, shouldNotGroupWhenFull: true };
    }
    return out;
}
