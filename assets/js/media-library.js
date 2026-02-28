/**
 * Copyright (c) 2026 徐州邻兔跃IT工作室
 * 联系邮箱：lintuyue@qq.com
 * 官方网站：lintuyue.com
 * 技术交流QQ群：550133641
 * 
 * 版权所有，禁止未经授权的复制、传播、修改或商业使用。
 * 未经开发者书面授权，任何单位或个人不得以任何形式复制、传播、
 * 展示、修改本软件或制作衍生作品。
 */
let mediaLibraryCallback = null;
let mediaLibraryWindow = null;

function openMediaLibrary(inputId, callback) {
    mediaLibraryCallback = callback || function(url) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = url;
            const preview = document.getElementById(inputId + '_preview');
            if (preview) {
                preview.src = url;
                preview.style.display = 'block';
            }
        }
    };

    const existingModal = document.getElementById('mediaLibraryModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'mediaLibraryModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    const iframe = document.createElement('iframe');
    iframe.src = 'lty-admin/media.php?mode=select';
    iframe.style.cssText = `
        width: 90%;
        height: 90%;
        max-width: 1200px;
        max-height: 800px;
        border: none;
        border-radius: 8px;
        background: white;
    `;

    modal.appendChild(iframe);
    document.body.appendChild(modal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeMediaLibrary();
        }
    });

    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'mediaSelected') {
            if (mediaLibraryCallback) {
                mediaLibraryCallback(event.data.url);
            }
            closeMediaLibrary();
        }
        if (event.data && event.data.type === 'mediaLibraryClosed') {
            closeMediaLibrary();
        }
    });

    mediaLibraryWindow = iframe.contentWindow;
}

function closeMediaLibrary() {
    const modal = document.getElementById('mediaLibraryModal');
    if (modal) {
        modal.remove();
    }
    mediaLibraryCallback = null;
    mediaLibraryWindow = null;
}

window.openMediaLibrary = openMediaLibrary;
window.closeMediaLibrary = closeMediaLibrary;
