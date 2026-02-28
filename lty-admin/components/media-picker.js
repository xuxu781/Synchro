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
const MediaPicker = {
    isOpen: false,
    items: [],
    selectedItems: [],
    currentCallback: null,
    multiple: false,
    searchTimer: null,
    currentPage: 1,
    totalPages: 1,
    
    open: function(options = {}) {
        this.multiple = options.multiple || false;
        this.currentCallback = options.onSelect || null;
        this.selectedItems = [];
        
        document.getElementById('mediaPickerModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        this.isOpen = true;
        
        this.load();
    },
    
    close: function() {
        const modal = document.getElementById('mediaPickerModal');
        if (modal) {
            modal.classList.remove('active');
        }
        document.body.style.overflow = '';
        this.isOpen = false;
        this.selectedItems = [];
        this.currentCallback = null;
    },
    
    load: function(page = 1) {
        const grid = document.getElementById('mediaPickerGrid');
        const loading = document.getElementById('mediaPickerLoading');
        const empty = document.getElementById('mediaPickerEmpty');
        
        grid.innerHTML = '';
        loading.style.display = 'flex';
        empty.style.display = 'none';
        
        const search = document.getElementById('mediaPickerSearch').value;
        const type = document.getElementById('mediaPickerType').value;
        
        let url = `api/media.php?page=${page}&per_page=40`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (type) url += `&filter_type=${type}`;
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success && data.data && data.data.length > 0) {
                    this.items = data.data;
                    this.currentPage = data.pagination.page;
                    this.totalPages = data.pagination.total_pages;
                    this.render();
                } else {
                    empty.style.display = 'flex';
                }
            })
            .catch(err => {
                loading.style.display = 'none';
                console.error('Load media error:', err);
            });
    },
    
    render: function() {
        const grid = document.getElementById('mediaPickerGrid');
        grid.innerHTML = '';
        
        this.items.forEach(item => {
            const isSelected = this.selectedItems.some(s => s.id === item.id);
            const isMissing = item.is_missing || false;
            const el = document.createElement('div');
            el.className = `media-picker-item${isSelected ? ' selected' : ''}${isMissing ? ' missing' : ''}`;
            el.dataset.id = item.id;
            el.onclick = () => this.toggleSelect(item);
            
            let preview = '';
            if (isMissing) {
                preview = `<div class="broken-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <line x1="21" y1="15" x2="15" y2="9"></line>
                        <line x1="15" y1="15" x2="21" y2="9"></line>
                    </svg>
                    <span>丢失</span>
                </div>`;
            } else if (item.file_type === 'image') {
                preview = `<img src="${this.escapeHtml(item.file_url)}" alt="" loading="lazy" onerror="MediaPicker.handleImageError(this)">`;
            } else if (item.file_type === 'video') {
                preview = '<span class="icon">🎬</span>';
            } else if (item.file_type === 'document') {
                preview = '<span class="icon">📄</span>';
            } else {
                preview = '<span class="icon">📁</span>';
            }
            
            el.innerHTML = `
                <div class="media-picker-item-preview">${preview}</div>
                <div class="media-picker-item-check">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="media-picker-item-name">${this.escapeHtml(item.filename || item.original_name)}</div>
            `;
            
            grid.appendChild(el);
        });
        
        if (this.totalPages > 1) {
            const pagination = document.createElement('div');
            pagination.className = 'media-picker-pagination';
            pagination.style.cssText = 'grid-column: 1 / -1; display: flex; justify-content: center; gap: 8px; margin-top: 16px;';
            
            if (this.currentPage > 1) {
                pagination.innerHTML += `<button class="btn btn-outline" onclick="MediaPicker.load(${this.currentPage - 1})">上一页</button>`;
            }
            
            pagination.innerHTML += `<span style="padding: 8px 12px; color: var(--text-muted);">第 ${this.currentPage} / ${this.totalPages} 页</span>`;
            
            if (this.currentPage < this.totalPages) {
                pagination.innerHTML += `<button class="btn btn-outline" onclick="MediaPicker.load(${this.currentPage + 1})">下一页</button>`;
            }
            
            grid.appendChild(pagination);
        }
        
        this.updateSelectedInfo();
    },
    
    toggleSelect: function(item) {
        const index = this.selectedItems.findIndex(s => s.id == item.id);
        
        if (index > -1) {
            this.selectedItems.splice(index, 1);
        } else {
            if (!this.multiple) {
                this.selectedItems = [];
            }
            this.selectedItems.push(item);
        }
        
        document.querySelectorAll('.media-picker-item').forEach(el => {
            const id = el.dataset.id;
            const isSelected = this.selectedItems.some(s => s.id == id);
            el.classList.toggle('selected', isSelected);
        });
        
        this.updateSelectedInfo();
    },
    
    updateSelectedInfo: function() {
        const info = document.getElementById('mediaPickerSelectedInfo');
        const count = this.selectedItems.length;
        
        if (count > 0) {
            info.textContent = `已选择 ${count} 个文件`;
        } else {
            info.textContent = '';
        }
    },
    
    confirm: function() {
        if (this.selectedItems.length === 0) {
            alert('请选择文件');
            return;
        }
        
        try {
            if (this.currentCallback) {
                if (this.multiple) {
                    this.currentCallback(this.selectedItems.map(item => item.file_url));
                } else {
                    this.currentCallback(this.selectedItems[0].file_url);
                }
            }
        } catch (e) {
            console.error('Callback error:', e);
        }
        
        this.close();
    },
    
    search: function(value) {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            this.load(1);
        }, 300);
    },
    
    filterByType: function(type) {
        this.load(1);
    },
    
    upload: function(files) {
        if (!files || files.length === 0) return;
        
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        
        const loading = document.getElementById('mediaPickerLoading');
        loading.style.display = 'flex';
        
        fetch('api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success) {
                const count = data.uploaded ? data.uploaded.length : files.length;
                alert('成功上传 ' + count + ' 个文件');
                this.load(1);
            } else {
                alert(data.message || '上传失败');
            }
        })
        .catch(err => {
            console.error('Upload error:', err);
            alert('上传失败: ' + err.message);
            loading.style.display = 'none';
        });
        
        document.getElementById('mediaPickerFileInput').value = '';
    },
    
    handleImageError: function(img) {
        if (img.dataset.errorHandled) return;
        img.dataset.errorHandled = 'true';
        
        const placeholder = document.createElement('div');
        placeholder.className = 'broken-placeholder';
        placeholder.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <line x1="21" y1="15" x2="15" y2="9"></line>
                <line x1="15" y1="15" x2="21" y2="9"></line>
            </svg>
            <span>丢失</span>
        `;
        
        img.parentNode.replaceChild(placeholder, img);
    },
    
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
