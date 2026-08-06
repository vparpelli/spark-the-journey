<?php
/*
 * Template Name: Spark Hub – Notes & College Tracker
 * Template Post Type: page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $website;
get_template_part( '/inc/base/GeneralClass' );
$website = new GeneralClass();
$website->fields();

$api_base = esc_url( get_rest_url( null, 'spark-hub/v1' ) );

get_header();
?>

<main role="main" class="smh-page">
  <div class="smh-page__inner">

    <!-- SECTION 1: Historical Progress Notes -->
    <section class="smh-section" id="progress-notes">
      <h2 class="smh-section-title">Add On: All Mentors, Historical Progress Notes</h2>
      <div class="smh-card">
        <div class="smh-card__title">Past Progress Notes</div>
        <div class="smh-notes-grid" id="smh-notes-grid">
          <!-- populated by JS -->
        </div>
        <button class="smh-add-note-btn" id="smh-add-note">+ Add New Note</button>
      </div>
    </section>

    <!-- SECTION 2: College Tracker -->
    <section class="smh-section" id="college-tracker">
      <h2 class="smh-section-title">Add On: HS Senior Students &amp; Mentors, College App / Enrollment Steps</h2>
      <div class="smh-card">
        <div class="smh-card__title">College Tracker</div>
        <div class="smh-college-tracker">

          <div class="smh-update-form">
            <h3>Record a College Update</h3>
            <div class="smh-form-field">
              <label for="cu-q1">Question 1</label>
              <input type="text" id="cu-q1" name="question_1">
            </div>
            <div class="smh-form-field">
              <label for="cu-q2">Question 2</label>
              <input type="text" id="cu-q2" name="question_2">
            </div>
            <div class="smh-form-field">
              <label for="cu-q3">Question 3</label>
              <textarea id="cu-q3" name="question_3"></textarea>
            </div>
            <div class="smh-radio-groups">
              <div class="smh-radio-group">
                <label>Question 4</label>
                <?php foreach ( [ 'A', 'B', 'C', 'D', 'E' ] as $opt ) : ?>
                <div class="smh-radio-option">
                  <input type="radio" name="question_4" value="<?php echo esc_attr( $opt ); ?>" id="q4-<?php echo esc_attr( $opt ); ?>">
                  <label for="q4-<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></label>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="smh-radio-group">
                <label>Question 5</label>
                <?php foreach ( [ '1', '2', '3', '4', '5' ] as $opt ) : ?>
                <div class="smh-radio-option">
                  <input type="radio" name="question_5" value="<?php echo esc_attr( $opt ); ?>" id="q5-<?php echo esc_attr( $opt ); ?>">
                  <label for="q5-<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <button type="submit" class="smh-submit-btn">Submit Update</button>
          </div>

          <div class="smh-semester">
            <h3>Fall Semester</h3>
            <ul class="smh-checklist">
              <?php foreach ( SMH_COLLEGE_FALL_STEPS as $item ) : ?>
              <li>
                <input type="checkbox" aria-label="<?php echo esc_attr( $item ); ?>">
                <span><?php echo esc_html( $item ); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="smh-semester">
            <h3>Spring Semester</h3>
            <ul class="smh-checklist">
              <?php foreach ( SMH_COLLEGE_SPRING_STEPS as $item ) : ?>
              <li>
                <input type="checkbox" aria-label="<?php echo esc_attr( $item ); ?>">
                <span><?php echo esc_html( $item ); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>

        </div>
      </div>
    </section>

  </div>
</main>

<div class="smh-toast" id="smh-toast"></div>

<script>
(function () {
    const API  = '<?php echo $api_base; ?>';
    const grid = document.getElementById('smh-notes-grid');
    const toast = document.getElementById('smh-toast');

    function showToast(msg) {
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2500);
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderCard(note) {
        const card = document.createElement('div');
        card.className = 'smh-note-card';
        card.dataset.id = note.id;
        card.innerHTML = `
            <div class="smh-note-header">
                <span class="smh-note-date-label">Date Logged: <span class="smh-date-display">${escHtml(note.date)}</span></span>
                <input type="date" class="smh-note-date-input" value="${escHtml(note.date)}" style="display:none">
                <div class="smh-note-actions">
                    <button class="smh-btn-icon edit-btn">Edit</button>
                    <button class="smh-btn-icon save save-btn" style="display:none">Save</button>
                    <button class="smh-btn-icon cancel-btn" style="display:none">Cancel</button>
                    <button class="smh-btn-icon danger delete-btn">Delete</button>
                </div>
            </div>
            <div class="smh-note-text">${escHtml(note.note)}</div>
            <textarea class="smh-note-textarea" style="display:none">${escHtml(note.note)}</textarea>
        `;

        const editBtn     = card.querySelector('.edit-btn');
        const saveBtn     = card.querySelector('.save-btn');
        const cancelBtn   = card.querySelector('.cancel-btn');
        const deleteBtn   = card.querySelector('.delete-btn');
        const textDiv     = card.querySelector('.smh-note-text');
        const textarea    = card.querySelector('.smh-note-textarea');
        const dateDisplay = card.querySelector('.smh-date-display');
        const dateInput   = card.querySelector('.smh-note-date-input');

        function enterEdit() {
            card.classList.add('is-editing');
            textDiv.style.display                   = 'none';
            textarea.style.display                  = 'block';
            dateDisplay.parentElement.style.display = 'none';
            dateInput.style.display                 = 'inline-block';
            editBtn.style.display                   = 'none';
            saveBtn.style.display                   = 'inline-block';
            cancelBtn.style.display                 = 'inline-block';
            textarea.focus();
        }

        function exitEdit() {
            card.classList.remove('is-editing');
            textDiv.style.display                   = 'block';
            textarea.style.display                  = 'none';
            dateDisplay.parentElement.style.display = '';
            dateInput.style.display                 = 'none';
            editBtn.style.display                   = 'inline-block';
            saveBtn.style.display                   = 'none';
            cancelBtn.style.display                 = 'none';
        }

        editBtn.addEventListener('click', enterEdit);

        cancelBtn.addEventListener('click', () => {
            textarea.value  = textDiv.textContent;
            dateInput.value = dateDisplay.textContent;
            exitEdit();
        });

        saveBtn.addEventListener('click', async () => {
            const newDate = dateInput.value;
            const newNote = textarea.value.trim();
            if (!newNote) return;
            saveBtn.disabled = true;
            try {
                const res = await fetch(`${API}/notes/${note.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date: newDate, note: newNote }),
                });
                if (!res.ok) throw new Error();
                const updated = await res.json();
                note.date = updated.date;
                note.note = updated.note;
                textDiv.textContent     = updated.note;
                dateDisplay.textContent = updated.date;
                dateInput.value         = updated.date;
                exitEdit();
                resortGrid();
                showToast('Note saved.');
            } catch {
                showToast('Error saving note.');
            } finally {
                saveBtn.disabled = false;
            }
        });

        deleteBtn.addEventListener('click', async () => {
            if (!confirm('Delete this note?')) return;
            deleteBtn.disabled = true;
            try {
                const res = await fetch(`${API}/notes/${note.id}`, { method: 'DELETE' });
                if (!res.ok) throw new Error();
                card.remove();
                showToast('Note deleted.');
            } catch {
                showToast('Error deleting note.');
                deleteBtn.disabled = false;
            }
        });

        return card;
    }

    function resortGrid() {
        const cards = Array.from(grid.querySelectorAll('.smh-note-card'));
        cards.sort((a, b) =>
            new Date(b.querySelector('.smh-date-display').textContent) -
            new Date(a.querySelector('.smh-date-display').textContent)
        );
        cards.forEach(c => grid.appendChild(c));
    }

    async function loadNotes() {
        grid.innerHTML = '<p style="color:#888;font-size:.85rem">Loading…</p>';
        try {
            const res   = await fetch(`${API}/notes`);
            const notes = await res.json();
            notes.sort((a, b) => new Date(b.date) - new Date(a.date));
            grid.innerHTML = '';
            if (notes.length === 0) {
                grid.innerHTML = '<p style="color:#888;font-size:.85rem;grid-column:1/-1">No notes yet.</p>';
            }
            notes.forEach(n => grid.appendChild(renderCard(n)));
        } catch {
            grid.innerHTML = '<p style="color:#c00;font-size:.85rem">Failed to load notes.</p>';
        }
    }

    document.getElementById('smh-add-note').addEventListener('click', async () => {
        const today  = new Date().toISOString().slice(0, 10);
        const tmpNote = { id: 'new-' + Date.now(), date: today, note: '' };
        const card    = renderCard(tmpNote);
        grid.prepend(card);

        const editBtn     = card.querySelector('.edit-btn');
        const saveBtn     = card.querySelector('.save-btn');
        const cancelBtn   = card.querySelector('.cancel-btn');
        const textDiv     = card.querySelector('.smh-note-text');
        const textarea    = card.querySelector('.smh-note-textarea');
        const dateDisplay = card.querySelector('.smh-date-display');
        const dateInput   = card.querySelector('.smh-note-date-input');

        card.classList.add('is-editing');
        textDiv.style.display                   = 'none';
        textarea.style.display                  = 'block';
        dateDisplay.parentElement.style.display = 'none';
        dateInput.style.display                 = 'inline-block';
        editBtn.style.display                   = 'none';
        saveBtn.style.display                   = 'inline-block';
        cancelBtn.style.display                 = 'inline-block';
        textarea.focus();

        cancelBtn.addEventListener('click', () => card.remove(), { once: true });

        saveBtn.addEventListener('click', async () => {
            const newDate  = dateInput.value;
            const noteText = textarea.value.trim();
            if (!noteText) { textarea.focus(); return; }
            saveBtn.disabled = true;
            try {
                const res = await fetch(`${API}/notes`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ date: newDate, note: noteText }),
                });
                if (!res.ok) throw new Error();
                const created = await res.json();
                card.replaceWith(renderCard(created));
                showToast('Note added.');
            } catch {
                showToast('Error adding note.');
                saveBtn.disabled = false;
            }
        }, { once: true });
    });

    loadNotes();
})();
</script>

<?php get_footer(); ?>
