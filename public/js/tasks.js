(() => {
    'use strict';

    const API_URL = document.querySelector('meta[name="tasks-api"]').content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const OPTIONS = JSON.parse(document.getElementById('task-options').textContent);
    const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const PRIORITY_WEIGHT = { high: 3, medium: 2, low: 1 };

    const $ = (id) => document.getElementById(id);
    const els = {
        form: $('task-form'),
        input: $('task-title'),
        category: $('new-task-category'),
        priority: $('new-task-priority'),
        due: $('new-task-due'),
        submit: $('task-submit'),
        formError: $('form-error'),
        newImage: $('new-task-image'),
        preview: $('attach-preview'),
        previewImg: $('attach-preview-img'),
        previewName: $('attach-preview-name'),
        previewRemove: $('attach-preview-remove'),
        taskImage: $('task-image-input'),
        search: $('search'),
        sort: $('sort'),
        list: $('task-list'),
        template: $('task-item-template'),
        empty: $('empty-state'),
        emptyTitle: $('empty-title'),
        emptyHint: $('empty-hint'),
        footer: $('list-footer'),
        footerText: $('list-footer-text'),
        clearCompleted: $('clear-completed'),
        statusFilters: document.querySelectorAll('[data-status]'),
        categoryFilters: document.querySelectorAll('[data-category]'),
        summary: $('hero-summary'),
        progressFill: $('progress-fill'),
        progressLabel: $('progress-label'),
        dialog: $('edit-dialog'),
        editForm: $('edit-form'),
        editError: $('edit-error'),
        lightbox: $('lightbox'),
        lightboxImg: $('lightbox-img'),
        lightboxCaption: $('lightbox-caption'),
        toasts: $('toasts'),
    };

    const state = {
        tasks: JSON.parse($('initial-tasks').textContent),
        status: 'all',
        category: 'all',
        query: '',
        sort: 'newest',
        newImage: null,
        imageTarget: null,
        editing: null,
    };

    /* ---------------- Tiện ích ---------------- */

    const pad = (n) => String(n).padStart(2, '0');
    const isoDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const todayIso = () => isoDate(new Date());
    const addDays = (days) => { const d = new Date(); d.setDate(d.getDate() + days); return isoDate(d); };
    const daysFromToday = (iso) => Math.round((new Date(`${iso}T00:00`) - new Date(`${todayIso()}T00:00`)) / 86400000);
    const fold = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase();

    const isOverdue = (t) => !t.completed && !!t.due_date && t.due_date < todayIso();
    const isDueToday = (t) => t.due_date === todayIso();

    const STATUS_FILTERS = {
        all: () => true,
        active: (t) => !t.completed,
        today: isDueToday,
        overdue: isOverdue,
        completed: (t) => t.completed,
    };

    const SORTERS = {
        newest: (a, b) => b.id - a.id,
        due: (a, b) => (a.due_date || '9999').localeCompare(b.due_date || '9999') || b.id - a.id,
        priority: (a, b) => PRIORITY_WEIGHT[b.priority] - PRIORITY_WEIGHT[a.priority] || SORTERS.due(a, b),
        title: (a, b) => a.title.localeCompare(b.title, 'vi'),
    };

    function dueLabel(task) {
        const diff = daysFromToday(task.due_date);
        if (diff === 0) return { text: 'Hôm nay', cls: 'is-today' };
        if (diff === 1) return { text: 'Ngày mai', cls: '' };
        if (diff === -1 && !task.completed) return { text: 'Quá hạn hôm qua', cls: 'is-overdue' };
        if (diff < 0 && !task.completed) return { text: `Quá hạn ${-diff} ngày`, cls: 'is-overdue' };
        const [y, m, d] = task.due_date.split('-');
        return { text: y === String(new Date().getFullYear()) ? `${d}/${m}` : `${d}/${m}/${y}`, cls: '' };
    }

    async function api(method, url, body) {
        const isForm = body instanceof FormData;
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken };
        if (body && !isForm) headers['Content-Type'] = 'application/json';

        const response = await fetch(url, {
            method,
            headers,
            body: body ? (isForm ? body : JSON.stringify(body)) : undefined,
        });

        // Hết phiên đăng nhập hoặc token CSRF hết hạn: tải lại để về trang đăng nhập.
        if (response.status === 401 || response.status === 419) {
            window.location.reload();
            throw new Error('Phiên đăng nhập đã hết hạn.');
        }
        if (response.status === 204) return null;

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 413) throw new Error('Ảnh quá lớn.');
            const firstError = data.errors && Object.values(data.errors)[0][0];
            throw new Error(firstError || data.message || 'Đã có lỗi xảy ra, vui lòng thử lại.');
        }
        return data.data;
    }

    function validateImage(file) {
        if (!IMAGE_TYPES.includes(file.type)) return 'File phải là ảnh (jpg, png, gif, webp).';
        if (file.size > MAX_IMAGE_BYTES) return 'Ảnh không được lớn hơn 5MB.';
        return null;
    }

    function toast(message, type = 'info') {
        const el = document.createElement('div');
        el.className = `toast toast--${type}`;
        el.textContent = message;
        els.toasts.append(el);
        setTimeout(() => {
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 300);
        }, 2600);
    }

    /**
     * Đặt nội dung cho thẻ nhỏ: icon SVG (từ bộ icon trong trang) + chữ.
     */
    function setTag(el, icon, text) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        svg.setAttribute('class', 'icon');
        svg.setAttribute('aria-hidden', 'true');
        use.setAttribute('href', `#i-${icon}`);
        svg.append(use);
        el.replaceChildren(svg, document.createTextNode(text));
    }

    /* ---------------- Hiển thị ---------------- */

    function visibleTasks() {
        const query = fold(state.query.trim());
        return state.tasks
            .filter(STATUS_FILTERS[state.status])
            .filter((t) => state.category === 'all' || t.category === state.category)
            .filter((t) => !query || fold(t.title).includes(query))
            .sort(SORTERS[state.sort]);
    }

    function renderTask(task) {
        const li = els.template.content.firstElementChild.cloneNode(true);
        const checkbox = li.querySelector('input[type="checkbox"]');
        const thumb = li.querySelector('.task__thumb');
        const setImageBtn = li.querySelector('[data-action="set-image"]');
        const category = OPTIONS.categories[task.category];
        const priority = OPTIONS.priorities[task.priority];
        const imageLabel = task.image_url ? 'Đổi ảnh' : 'Thêm ảnh';

        li.dataset.id = task.id;
        li.classList.add(`prio--${task.priority}`);
        li.classList.toggle('is-completed', task.completed);
        li.classList.toggle('is-overdue', isOverdue(task));
        li.querySelector('.task__title').textContent = task.title;

        setTag(li.querySelector('.task__category'), category.icon, category.label);
        setTag(li.querySelector('.task__priority'), 'flag', priority.label);

        const dueTag = li.querySelector('.task__due');
        if (task.due_date) {
            const due = dueLabel(task);
            dueTag.hidden = false;
            setTag(dueTag, 'calendar', due.text);
            if (due.cls) dueTag.classList.add(due.cls);
        }

        checkbox.checked = task.completed;
        checkbox.setAttribute('aria-label', `Đánh dấu "${task.title}" là ${task.completed ? 'chưa hoàn thành' : 'hoàn thành'}`);

        thumb.hidden = !task.image_url;
        if (task.image_url) {
            thumb.querySelector('img').src = task.image_url;
            thumb.setAttribute('aria-label', `Xem ảnh của "${task.title}"`);
        }
        setImageBtn.title = imageLabel;
        setImageBtn.setAttribute('aria-label', imageLabel);
        li.querySelector('[data-action="remove-image"]').hidden = !task.image_url;

        checkbox.addEventListener('change', () => setCompleted(task, checkbox.checked, li));
        thumb.addEventListener('click', () => openLightbox(task));
        li.querySelector('[data-action="edit"]').addEventListener('click', () => openEditor(task));
        setImageBtn.addEventListener('click', () => {
            state.imageTarget = { task, li };
            els.taskImage.value = '';
            els.taskImage.click();
        });
        li.querySelector('[data-action="remove-image"]').addEventListener('click', () => removeImage(task, li));
        li.querySelector('[data-action="delete"]').addEventListener('click', () => removeTask(task, li));

        return li;
    }

    function renderSummary() {
        const total = state.tasks.length;
        const done = state.tasks.filter((t) => t.completed).length;
        const active = total - done;
        const overdue = state.tasks.filter(isOverdue).length;
        const today = state.tasks.filter((t) => isDueToday(t) && !t.completed).length;
        const percent = total ? Math.round((done / total) * 100) : 0;

        let summary;
        if (!total) summary = 'Chưa có công việc nào. Thêm việc đầu tiên ở bên dưới.';
        else if (!active) summary = 'Bạn đã hoàn thành tất cả công việc.';
        else {
            const parts = [`Bạn còn ${active} việc cần làm`];
            if (today) parts.push(`${today} việc đến hạn hôm nay`);
            if (overdue) parts.push(`${overdue} việc quá hạn`);
            summary = `${parts.join(', ')}.`;
        }

        els.summary.textContent = summary;
        els.progressFill.style.width = `${percent}%`;
        els.progressLabel.textContent = `${percent}% · ${done}/${total}`;

        for (const [status, test] of Object.entries(STATUS_FILTERS)) {
            document.querySelector(`[data-count="${status}"]`).textContent = state.tasks.filter(test).length;
        }
        document.querySelector('[data-cat-count="all"]').textContent = total;
        for (const key of Object.keys(OPTIONS.categories)) {
            document.querySelector(`[data-cat-count="${key}"]`).textContent = state.tasks.filter((t) => t.category === key).length;
        }

        els.footer.hidden = done === 0;
        els.footerText.textContent = `${done} việc đã xong`;
    }

    function renderEmpty(count) {
        els.empty.hidden = count > 0;
        if (count > 0) return;

        let text;
        if (!state.tasks.length) text = ['Chưa có công việc nào', 'Thêm công việc đầu tiên ở ô phía trên.'];
        else if (state.query.trim()) text = ['Không tìm thấy công việc phù hợp', 'Thử từ khóa khác hoặc bỏ bớt bộ lọc.'];
        else if (state.status === 'overdue') text = ['Không có việc nào quá hạn', 'Bạn đang làm đúng tiến độ.'];
        else if (state.status === 'today') text = ['Không có việc nào đến hạn hôm nay', 'Đặt hạn chót cho công việc để thấy chúng ở đây.'];
        else if (state.status === 'active') text = ['Không còn việc gì dang dở', 'Tuyệt vời, bạn đã hoàn thành tất cả!'];
        else if (state.status === 'completed') text = ['Chưa hoàn thành việc nào', 'Đánh dấu một công việc để thấy nó ở đây.'];
        else text = ['Danh mục này chưa có công việc', 'Chọn danh mục khác hoặc thêm việc mới.'];

        [els.emptyTitle.textContent, els.emptyHint.textContent] = text;
    }

    function render() {
        const tasks = visibleTasks();
        els.list.replaceChildren(...tasks.map(renderTask));
        renderSummary();
        renderEmpty(tasks.length);
    }

    function replaceTask(updated) {
        const index = state.tasks.findIndex((t) => t.id === updated.id);
        if (index !== -1) state.tasks[index] = updated;
    }

    /* ---------------- Thêm công việc ---------------- */

    function showFormError(message) {
        els.formError.textContent = message;
        els.formError.hidden = !message;
    }

    function setNewImage(file) {
        if (els.previewImg.src) URL.revokeObjectURL(els.previewImg.src);
        state.newImage = file;
        els.preview.hidden = !file;
        if (file) {
            els.previewImg.src = URL.createObjectURL(file);
            els.previewName.textContent = file.name;
        } else {
            els.previewImg.removeAttribute('src');
            els.newImage.value = '';
        }
    }

    async function addTask(event) {
        event.preventDefault();
        const title = els.input.value.trim();
        if (!title) {
            showFormError('Vui lòng nhập tên công việc.');
            els.input.focus();
            return;
        }

        const body = new FormData();
        body.append('title', title);
        body.append('category', els.category.value);
        body.append('priority', els.priority.value);
        if (els.due.value) body.append('due_date', els.due.value);
        if (state.newImage) body.append('image', state.newImage);

        els.submit.disabled = true;
        try {
            const task = await api('POST', API_URL, body);
            state.tasks.unshift(task);
            els.input.value = '';
            els.due.value = '';
            setNewImage(null);
            showFormError('');
            render();
            toast(`Đã thêm "${task.title}"`, 'success');
        } catch (error) {
            showFormError(error.message);
        } finally {
            els.submit.disabled = false;
            els.input.focus();
        }
    }

    /* ---------------- Thao tác trên từng công việc ---------------- */

    async function setCompleted(task, completed, li) {
        li.classList.add('is-busy');
        try {
            replaceTask(await api('PATCH', `${API_URL}/${task.id}`, { completed }));
            if (completed && state.tasks.every((t) => t.completed)) {
                toast('Bạn đã hoàn thành tất cả công việc.', 'success');
            }
        } catch (error) {
            toast(error.message, 'error');
        }
        render();
    }

    async function uploadImage(file) {
        const { task, li } = state.imageTarget;
        state.imageTarget = null;

        const invalid = validateImage(file);
        if (invalid) {
            toast(invalid, 'error');
            return;
        }

        const body = new FormData();
        body.append('image', file);

        li.classList.add('is-busy');
        try {
            replaceTask(await api('POST', `${API_URL}/${task.id}/image`, body));
            toast('Đã cập nhật ảnh', 'success');
        } catch (error) {
            toast(error.message, 'error');
        }
        render();
    }

    async function removeImage(task, li) {
        if (!confirm(`Gỡ ảnh khỏi công việc "${task.title}"?`)) return;

        li.classList.add('is-busy');
        try {
            replaceTask(await api('DELETE', `${API_URL}/${task.id}/image`));
            toast('Đã gỡ ảnh');
        } catch (error) {
            toast(error.message, 'error');
        }
        render();
    }

    async function removeTask(task, li) {
        if (!confirm(`Xóa công việc "${task.title}"?`)) return;

        li.classList.add('is-busy');
        try {
            await api('DELETE', `${API_URL}/${task.id}`);
            state.tasks = state.tasks.filter((t) => t.id !== task.id);
            toast(`Đã xóa "${task.title}"`);
        } catch (error) {
            toast(error.message, 'error');
        }
        render();
    }

    async function clearCompleted() {
        const count = state.tasks.filter((t) => t.completed).length;
        if (!count || !confirm(`Xóa ${count} việc đã hoàn thành?`)) return;

        els.clearCompleted.disabled = true;
        try {
            const result = await api('DELETE', `${API_URL}/completed`);
            state.tasks = state.tasks.filter((t) => !t.completed);
            toast(`Đã xóa ${result.deleted} việc đã xong`, 'success');
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            els.clearCompleted.disabled = false;
        }
        render();
    }

    /* ---------------- Sửa công việc ---------------- */

    function openEditor(task) {
        state.editing = task;
        const form = els.editForm;
        form.elements.title.value = task.title;
        form.elements.category.value = task.category;
        form.elements.priority.value = task.priority;
        form.elements.due_date.value = task.due_date || '';
        els.editError.hidden = true;
        els.dialog.showModal();
        form.elements.title.focus();
    }

    async function saveEdit(event) {
        event.preventDefault();
        const form = els.editForm;
        const task = state.editing;
        const title = form.elements.title.value.trim();
        if (!title) {
            els.editError.textContent = 'Vui lòng nhập tên công việc.';
            els.editError.hidden = false;
            return;
        }

        $('edit-save').disabled = true;
        try {
            replaceTask(await api('PUT', `${API_URL}/${task.id}`, {
                title,
                category: form.elements.category.value,
                priority: form.elements.priority.value,
                due_date: form.elements.due_date.value || null,
            }));
            els.dialog.close();
            render();
            toast('Đã lưu thay đổi', 'success');
        } catch (error) {
            els.editError.textContent = error.message;
            els.editError.hidden = false;
        } finally {
            $('edit-save').disabled = false;
        }
    }

    /* ---------------- Xem ảnh lớn ---------------- */

    function openLightbox(task) {
        els.lightboxImg.src = task.image_url;
        els.lightboxImg.alt = task.title;
        els.lightboxCaption.textContent = task.title;
        els.lightbox.hidden = false;
        els.lightbox.querySelector('.lightbox__close').focus();
    }

    function closeLightbox() {
        els.lightbox.hidden = true;
        els.lightboxImg.removeAttribute('src');
    }

    /* ---------------- Sự kiện ---------------- */

    els.statusFilters.forEach((btn) => btn.addEventListener('click', () => {
        state.status = btn.dataset.status;
        els.statusFilters.forEach((b) => b.classList.toggle('is-active', b === btn));
        render();
    }));

    els.categoryFilters.forEach((btn) => btn.addEventListener('click', () => {
        state.category = btn.dataset.category;
        els.categoryFilters.forEach((b) => b.classList.toggle('is-active', b === btn));
        render();
    }));

    els.search.addEventListener('input', () => { state.query = els.search.value; render(); });
    els.sort.addEventListener('change', () => { state.sort = els.sort.value; render(); });

    els.form.addEventListener('submit', addTask);
    els.input.addEventListener('input', () => showFormError(''));
    document.querySelectorAll('[data-quick-due]').forEach((btn) => btn.addEventListener('click', () => {
        els.due.value = addDays(Number(btn.dataset.quickDue));
    }));

    els.newImage.addEventListener('change', () => {
        const file = els.newImage.files[0];
        if (!file) return;
        const invalid = validateImage(file);
        if (invalid) {
            showFormError(invalid);
            els.newImage.value = '';
            return;
        }
        showFormError('');
        setNewImage(file);
    });
    els.previewRemove.addEventListener('click', () => setNewImage(null));

    els.taskImage.addEventListener('change', () => {
        if (els.taskImage.files[0] && state.imageTarget) uploadImage(els.taskImage.files[0]);
    });

    els.clearCompleted.addEventListener('click', clearCompleted);
    els.editForm.addEventListener('submit', saveEdit);
    $('edit-cancel').addEventListener('click', () => els.dialog.close());

    els.lightbox.addEventListener('click', (event) => {
        if (event.target === els.lightbox || event.target.closest('.lightbox__close')) closeLightbox();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !els.lightbox.hidden) closeLightbox();
    });

    const hour = new Date().getHours();
    $('greeting').textContent = hour < 11 ? 'Chào buổi sáng' : hour < 14 ? 'Chào buổi trưa' : hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối';
    $('today').textContent = new Date().toLocaleDateString('vi-VN', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });

    render();
})();
