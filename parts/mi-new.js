/**
 * NEW MI System UI - 0-4 Scale Multi-Source Observations
 */

// Global state
let miDomains = [];
let miSources = [];
let miContexts = [];
let currentMIStudent = null;

/**
 * Initialize MI system
 */
async function initMISystem() {
    try {
        showLoading();
        const [domains, sources, contexts] = await Promise.all([
            apiCall('getIntelligenceDomains'),
            apiCall('getObservationSources'),
            apiCall('getObservationContexts')
        ]);

        miDomains = domains;
        miSources = sources;
        miContexts = contexts;

        hideLoading();
    } catch (error) {
        console.error('Failed to initialize MI system:', error);
        showToast('خطا در بارگذاری سیستم هوش‌های چندگانه', 'error');
    }
}

/**
 * Open MI observation modal for a student
 */
async function openMIObservation(studentName) {
    if (!miDomains.length) await initMISystem();

    currentMIStudent = studentName;

    // Render modal
    const modalHTML = `
        <div class="modal-overlay" id="mi-observation-modal" onclick="if(event.target===this) closeMIObservationModal()">
            <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
                <div class="modal-header">
                    <h2>ثبت مشاهده هوش چندگانه</h2>
                    <button onclick="closeMIObservationModal()" class="btn-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>دانش‌آموز:</strong> ${escapeHtml(studentName)}
                    </div>

                    <form id="mi-observation-form" onsubmit="submitMIObservationForm(event)">
                        <!-- Domain Selection -->
                        <div class="form-group">
                            <label>حوزه هوش *</label>
                            <select id="mi-domain-select" class="form-control" required onchange="updateIndicatorsList()">
                                <option value="">انتخاب کنید...</option>
                                ${miDomains.map(d => `<option value="${d.id}">${d.icon} ${escapeHtml(d.name_fa)}</option>`).join('')}
                            </select>
                        </div>

                        <!-- Indicator Selection -->
                        <div class="form-group" id="mi-indicator-group" style="display:none;">
                            <label>شاخص رفتاری *</label>
                            <div id="mi-indicators-list"></div>
                        </div>

                        <!-- Score Selection -->
                        <div class="form-group" id="mi-score-group" style="display:none;">
                            <label>فراوانی مشاهده *</label>
                            <div class="mi-score-selector">
                                <label class="mi-score-option">
                                    <input type="radio" name="score" value="0" required>
                                    <span>۰<br><small>هرگز</small></span>
                                </label>
                                <label class="mi-score-option">
                                    <input type="radio" name="score" value="1">
                                    <span>۱<br><small>خیلی کم</small></span>
                                </label>
                                <label class="mi-score-option">
                                    <input type="radio" name="score" value="2">
                                    <span>۲<br><small>گاهی</small></span>
                                </label>
                                <label class="mi-score-option">
                                    <input type="radio" name="score" value="3">
                                    <span>۳<br><small>زیاد</small></span>
                                </label>
                                <label class="mi-score-option">
                                    <input type="radio" name="score" value="4">
                                    <span>۴<br><small>خیلی زیاد</small></span>
                                </label>
                            </div>
                            <label style="margin-top: 10px; display: flex; align-items: center;">
                                <input type="checkbox" id="mi-no-opportunity" onchange="toggleNoOpportunity()">
                                <span style="margin-right: 8px;">فرصت مشاهده نداشتم</span>
                            </label>
                        </div>

                        <!-- Source Selection -->
                        <div class="form-group">
                            <label>منبع مشاهده *</label>
                            <select id="mi-source-select" class="form-control" required>
                                <option value="">انتخاب کنید...</option>
                                ${miSources.map(s => `<option value="${s.id}">${escapeHtml(s.name_fa)}</option>`).join('')}
                            </select>
                        </div>

                        <!-- Context Selection -->
                        <div class="form-group">
                            <label>محیط مشاهده</label>
                            <select id="mi-context-select" class="form-control">
                                <option value="">انتخاب کنید...</option>
                                ${miContexts.map(c => `<option value="${c.id}">${escapeHtml(c.name_fa)}</option>`).join('')}
                            </select>
                        </div>

                        <!-- Evidence -->
                        <div class="form-group">
                            <label>شواهد و توضیحات (اختیاری)</label>
                            <textarea id="mi-evidence" class="form-control" rows="3" maxlength="1000"
                                      placeholder="توضیح مختصر درباره رفتار مشاهده شده..."></textarea>
                            <small style="color: #64748b;">حداکثر ۱۰۰۰ کاراکتر</small>
                        </div>

                        <!-- Date -->
                        <div class="form-group">
                            <label>تاریخ مشاهده</label>
                            <input type="date" id="mi-observed-date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-outline" onclick="closeMIObservationModal()">انصراف</button>
                            <button type="submit" class="btn btn-primary">ثبت مشاهده</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .mi-score-selector {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .mi-score-option {
            flex: 1;
            min-width: 80px;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 15px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .mi-score-option:hover {
            border-color: #4f46e5;
            background: #f0f0ff;
        }
        .mi-score-option input[type="radio"] {
            display: none;
        }
        .mi-score-option input[type="radio"]:checked + span {
            color: #4f46e5;
            font-weight: bold;
        }
        .mi-score-option input[type="radio"]:checked ~ span {
            border-color: #4f46e5;
        }
        .mi-score-option:has(input:checked) {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .mi-indicator-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .mi-indicator-item:hover {
            border-color: #4f46e5;
            background: #f8fafc;
        }
        .mi-indicator-item.selected {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .mi-indicator-item input[type="radio"] {
            margin-left: 8px;
        }
        .mi-polarity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 8px;
        }
        .mi-polarity-positive {
            background: #dcfce7;
            color: #166534;
        }
        .mi-polarity-negative {
            background: #fee2e2;
            color: #991b1b;
        }
        </style>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Update indicators list based on selected domain
 */
function updateIndicatorsList() {
    const domainId = parseInt(document.getElementById('mi-domain-select').value);
    const indicatorGroup = document.getElementById('mi-indicator-group');
    const scoreGroup = document.getElementById('mi-score-group');

    if (!domainId) {
        indicatorGroup.style.display = 'none';
        scoreGroup.style.display = 'none';
        return;
    }

    const domain = miDomains.find(d => d.id === domainId);
    if (!domain) return;

    const indicatorsList = document.getElementById('mi-indicators-list');
    indicatorsList.innerHTML = domain.indicators.map(ind => {
        const polarityClass = ind.indicator_category === 'positive' ? 'mi-polarity-positive' : 'mi-polarity-negative';
        const polarityText = ind.indicator_category === 'positive' ? 'مثبت' : 'منفی';

        return `
            <label class="mi-indicator-item" onclick="selectIndicator(${ind.id})">
                <input type="radio" name="indicator_id" value="${ind.id}" required>
                <span class="mi-polarity-badge ${polarityClass}">${polarityText}</span>
                <strong>${escapeHtml(ind.indicator_text)}</strong>
                ${ind.explanation ? `<br><small style="color: #64748b;">${escapeHtml(ind.explanation)}</small>` : ''}
            </label>
        `;
    }).join('');

    indicatorGroup.style.display = 'block';
    scoreGroup.style.display = 'block';
}

/**
 * Select an indicator
 */
function selectIndicator(indicatorId) {
    document.querySelectorAll('.mi-indicator-item').forEach(item => {
        item.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

/**
 * Toggle no opportunity checkbox
 */
function toggleNoOpportunity() {
    const noOpportunity = document.getElementById('mi-no-opportunity').checked;
    const scoreInputs = document.querySelectorAll('input[name="score"]');

    scoreInputs.forEach(input => {
        input.disabled = noOpportunity;
        if (noOpportunity) input.checked = false;
    });
}

/**
 * Submit MI observation form
 */
async function submitMIObservationForm(event) {
    event.preventDefault();

    const formData = {
        student_name: currentMIStudent,
        indicator_id: parseInt(document.querySelector('input[name="indicator_id"]:checked').value),
        source_id: parseInt(document.getElementById('mi-source-select').value),
        context_id: document.getElementById('mi-context-select').value || null,
        score: document.getElementById('mi-no-opportunity').checked
            ? null
            : parseInt(document.querySelector('input[name="score"]:checked')?.value),
        no_opportunity: document.getElementById('mi-no-opportunity').checked,
        evidence: document.getElementById('mi-evidence').value.trim(),
        observed_at: document.getElementById('mi-observed-date').value
    };

    // Validation
    if (!formData.no_opportunity && formData.score === null) {
        showToast('لطفاً فراوانی مشاهده را انتخاب کنید یا «فرصت مشاهده نداشتم» را علامت بزنید', 'error');
        return;
    }

    try {
        showLoading();
        const result = await apiCall('submitMIObservation', formData);
        hideLoading();

        if (result.success) {
            showToast(result.msg || 'مشاهده با موفقیت ثبت شد', 'success');
            closeMIObservationModal();
            // Refresh profile if open
            if (typeof openProfile === 'function') {
                openProfile(currentMIStudent);
            }
        } else {
            showToast(result.msg || 'خطا در ثبت مشاهده', 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('خطا در ارتباط با سرور', 'error');
        console.error('MI observation submission failed:', error);
    }
}

/**
 * Close MI observation modal
 */
function closeMIObservationModal() {
    const modal = document.getElementById('mi-observation-modal');
    if (modal) modal.remove();
    currentMIStudent = null;
}

/**
 * Render new MI profile in student profile view
 */
async function renderStudentMIProfileNew(studentName) {
    try {
        showLoading();
        const profile = await apiCall('getStudentMIProfileNew', studentName);
        hideLoading();

        if (!profile.success && profile.msg) {
            return `<div class="alert alert-info">${escapeHtml(profile.msg)}</div>`;
        }

        let html = '<div class="mi-profile-new">';

        // Header with add button
        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>پروفایل هوش‌های چندگانه (سیستم جدید)</h3>
                <button class="btn btn-primary" onclick="openMIObservation('${escapeHtml(studentName)}')">
                    <i class="fas fa-plus"></i> ثبت مشاهده جدید
                </button>
            </div>
        `;

        if (!profile.has_data) {
            html += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    هنوز مشاهده‌ای در سیستم جدید ثبت نشده است. برای شروع، دکمه «ثبت مشاهده جدید» را بزنید.
                </div>
            `;
        } else {
            // Render domains
            html += '<div class="mi-domains-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px;">';

            profile.domains.forEach(item => {
                const score = item.score !== null ? item.score.toFixed(1) : '—';
                const scorePercent = item.score !== null ? (item.score / item.max_score * 100) : 0;
                const coverageColor = item.coverage >= 70 ? '#10b981' : (item.coverage >= 40 ? '#f59e0b' : '#ef4444');

                html += `
                    <div class="card" style="padding: 15px; border: 1px solid #e5e7eb; border-radius: 12px;">
                        <div style="font-size: 2rem; text-align: center; margin-bottom: 10px;">${item.domain.icon}</div>
                        <div style="font-weight: bold; text-align: center; margin-bottom: 8px; font-size: 0.9rem;">
                            ${escapeHtml(item.domain.name_fa)}
                        </div>
                        <div style="text-align: center; font-size: 1.8rem; font-weight: bold; color: #4f46e5; margin-bottom: 10px;">
                            ${toPersianNum(score)} / ${toPersianNum(item.max_score)}
                        </div>
                        <div style="background: #f1f5f9; border-radius: 8px; height: 8px; overflow: hidden; margin-bottom: 8px;">
                            <div style="background: linear-gradient(90deg, #4f46e5, #818cf8); height: 100%; width: ${scorePercent}%; transition: width 0.3s;"></div>
                        </div>
                        <div style="text-align: center; font-size: 0.75rem; color: #64748b;">
                            پوشش: <span style="color: ${coverageColor}; font-weight: bold;">${toPersianNum(item.coverage.toFixed(0))}%</span>
                            (${toPersianNum(item.observation_count)} مشاهده)
                        </div>
                    </div>
                `;
            });

            html += '</div>';
        }

        // Legacy history
        if (profile.legacy_history && profile.legacy_history.length > 0) {
            html += `
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                    <h4 style="color: #64748b; font-size: 0.9rem; margin-bottom: 15px;">
                        <i class="fas fa-archive"></i> مشاهدات قبلی (سیستم قدیم)
                    </h4>
                    <div style="max-height: 300px; overflow-y: auto;">
            `;

            profile.legacy_history.forEach(item => {
                const scoreLabel = item.score === 1 ? '✅ مثبت' : (item.score === -1 ? '❌ منفی' : '⚪ خنثی');
                html += `
                    <div style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; opacity: 0.7;">
                        <span style="color: #64748b;">${toPersianNum(item.date)}</span> —
                        <strong>${escapeHtml(item.type)}</strong>: ${escapeHtml(item.behavior)}
                        <span style="margin-right: 8px;">${scoreLabel}</span>
                    </div>
                `;
            });

            html += '</div></div>';
        }

        html += '</div>';

        return html;

    } catch (error) {
        hideLoading();
        console.error('Failed to load MI profile:', error);
        return '<div class="alert alert-error">خطا در بارگذاری پروفایل هوش‌های چندگانه</div>';
    }
}

/**
 * Helper function for API calls
 */
async function apiCall(action, ...args) {
    const response = await fetch('api.php?action=' + action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken || ''
        },
        body: JSON.stringify({ args: args })
    });

    if (!response.ok) {
        throw new Error('Network response was not ok');
    }

    return await response.json();
}

/**
 * HTML escape helper
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
