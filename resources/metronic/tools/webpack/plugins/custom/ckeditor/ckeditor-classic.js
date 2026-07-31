// CKEditor 5 - https://ckeditor.com/ (new single package)
import {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Link,
    Paragraph,
    Heading,
    List,
    BlockQuote,
    Table,
    Undo,
    Alignment,
    Font,
    RemoveFormat,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import '@ckeditor/ckeditor5-editor-classic/theme/classiceditor.css';
import { normalizeConfig } from './ckeditor-config.js';

const plugins = [
    Essentials,
    Bold,
    Italic,
    Link,
    Paragraph,
    Heading,
    List,
    BlockQuote,
    Table,
    Undo,
    Alignment,
    Font,
    RemoveFormat,
];

const defaultToolbarItems = ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'alignment', '|', 'fontSize', 'fontFamily', '|', 'removeFormat', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo'];

window.ClassicEditor = {
    create(element, config) {
        const cfg = normalizeConfig(config || {});
        const items = (config?.toolbar && Array.isArray(config.toolbar.items)) ? config.toolbar.items : defaultToolbarItems;
        const toolbarConfig = (cfg.toolbar && !Array.isArray(cfg.toolbar) && cfg.toolbar.items) ? cfg.toolbar : { items, shouldNotGroupWhenFull: true };
        return ClassicEditor.create(element, {
            plugins,
            ...cfg,
            toolbar: toolbarConfig,
        });
    },
};
