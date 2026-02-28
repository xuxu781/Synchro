<div id="mediaPickerModal" class="media-picker-modal">
    <div class="media-picker-overlay" onclick="MediaPicker.close()"></div>
    <div class="media-picker-container">
        <div class="media-picker-header">
            <h3>选择媒体</h3>
            <button class="media-picker-close" onclick="MediaPicker.close()">×</button>
        </div>
        <div class="media-picker-toolbar">
            <div class="media-picker-search">
                <input type="text" id="mediaPickerSearch" placeholder="搜索文件..." oninput="MediaPicker.search(this.value)">
            </div>
            <div class="media-picker-filters">
                <select id="mediaPickerType" onchange="MediaPicker.filterByType(this.value)">
                    <option value="">全部类型</option>
                    <option value="image">图片</option>
                    <option value="video">视频</option>
                    <option value="document">文档</option>
                </select>
            </div>
            <div class="media-picker-upload">
                <button class="btn btn-primary" onclick="document.getElementById('mediaPickerFileInput').click()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    上传文件
                </button>
                <input type="file" id="mediaPickerFileInput" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" style="display:none" onchange="MediaPicker.upload(this.files)">
            </div>
        </div>
        <div class="media-picker-body">
            <div id="mediaPickerGrid" class="media-picker-grid"></div>
            <div id="mediaPickerLoading" class="media-picker-loading" style="display:none">
                <div class="spinner"></div>
                <span>加载中...</span>
            </div>
            <div id="mediaPickerEmpty" class="media-picker-empty" style="display:none">
                <div style="font-size:48px;margin-bottom:16px">📷</div>
                <div>暂无媒体文件</div>
            </div>
        </div>
        <div class="media-picker-footer">
            <div class="media-picker-selected-info" id="mediaPickerSelectedInfo"></div>
            <div class="media-picker-actions">
                <button class="btn btn-outline" onclick="MediaPicker.close()">取消</button>
                <button class="btn btn-primary" id="mediaPickerConfirm" onclick="MediaPicker.confirm()">选择</button>
            </div>
        </div>
    </div>
</div>
