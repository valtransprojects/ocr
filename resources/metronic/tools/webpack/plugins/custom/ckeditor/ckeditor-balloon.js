// CKEditor 5 - https://ckeditor.com/ (new single package)
import {
    BalloonEditor,
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

window.BalloonEditor = {
    create(element, config) {
        return BalloonEditor.create(element, {
            plugins,
            toolbar: config?.toolbar?.items || ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'alignment', '|', 'fontSize', 'fontFamily', '|', 'removeFormat', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo'],
            ...normalizeConfig(config || {}),
        });
    },
};
