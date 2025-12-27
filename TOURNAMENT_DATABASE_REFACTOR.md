# Tournament Database-Driven Progress Tracking - Refactoring Documentation

**Date:** 27 December 2025

## 🎯 CILJ REFAKTORINGA

Prelazak sa localStorage tracking sistema na **database-driven progress tracking** - da se pamti napredak korisnika u bazi podataka umesto u browser localStorage.

Razlog: localStorage ne radi cross-device, nije persistent, i ne daje nam kontrolu nad podacima.

---

## 📁 IZMENJENI FAJLOVI

### 1. **inc/voting/tournament/api/tournament-ajax.php**

#### Dodato 3 nove helper funkcije:

**A) `yuv_find_next_unvoted_match($tournament_id, $stage, $user_id, $user_ip)`**

- **Svrha**: Pronalazi sledeći meč u kom korisnik nije glasao
- **Logika**:
  - Koristi LEFT JOIN sa `voting_list_votes` tabelom
  - WHERE `v.id IS NULL` (nema vote record)
  - Za logged-in: proverava `user_id > 0`
  - Za guest: proverava `user_id = 0 AND ip_address`
- **Vraća**: `match_id` ili `null`
- **Poziva**: `yuv_get_match_data($match_id)` i vraća rezultat

**B) `yuv_get_match_data($match_id)`**

- **Svrha**: Vraća kompletan match objekat sa svim podacima za rendering
- **Struktura odgovora**:

```php
[
    'match_id' => (int),
    'stage' => 'of|qf|sf|final',
    'match_number' => (int),
    'end_time' => (timestamp),
    'contenders' => [
        [
            'id' => (int),
            'name' => (string),
            'description' => (string),
            'image' => (url)
        ],
        [...]
    ]
]
```

- **Uzima podatke iz**: `_voting_items` meta field

**C) `yuv_calculate_stage_progress($tournament_id, $stage, $user_id, $user_ip)`**

- **Svrha**: Računa napredak korisnika u trenutnoj fazi turnira
- **SQL Queries**:
  - `total`: COUNT svih mečeva u stage (JOIN na postmeta)
  - `voted`: COUNT DISTINCT voting_list_id iz votes tabele za user/IP
- **Struktura odgovora**:

```php
[
    'total' => (int),      // Ukupan broj mečeva
    'voted' => (int),      // Broj glasanih mečeva
    'remaining' => (int),  // Preostalo
    'percent' => (int)     // Procenat (0-100)
]
```

#### Izmenjena postojeća funkcija:

**`yuv_cast_tournament_vote_ajax()`**

- **Pre**: Vraćalo samo `next_match_url` i `next_match_id`
- **Posle**:

```php
// Nakon insert vote-a:
$tournament_id = get_post_meta($match_id, '_yuv_tournament_id', true);
$stage = get_post_meta($match_id, '_yuv_stage', true);

$next_match_data = yuv_find_next_unvoted_match($tournament_id, $stage, $user_id, $user_ip);
$progress = yuv_calculate_stage_progress($tournament_id, $stage, $user_id, $user_ip);

wp_send_json_success([
    'message' => 'Glas uspešno zabeležen!',
    'vote_id' => $wpdb->insert_id,
    'next_match' => $next_match_data,  // null ako nema više mečeva
    'progress' => $progress
]);
```

---

### 2. **js/tournament-carousel.js**

#### UKLONJENO:

```javascript
// Sve localStorage pozive
localStorage.getItem(`yuv_voted_${tournamentId}_${stage}`);
localStorage.setItem(`yuv_voted_${tournamentId}_${stage}`, votedMatches);
```

#### IZMENJENO:

**Linija ~16-19: Progress tracking inicijalizacija**

```javascript
// BILO:
let votedMatches = parseInt(
  localStorage.getItem(`yuv_voted_${tournamentId}_${stage}`) || 0
);

// SADA:
let votedMatches = parseInt(arena.data("voted-matches") || 0); // Iz data atributa
```

**Linija ~95-108: AJAX vote success handler**

```javascript
// BILO:
votedMatches++;
localStorage.setItem(`yuv_voted_${tournamentId}_${stage}`, votedMatches);
updateProgressBar();

if (votedMatches >= totalMatches) {
  showFinalBracket();
} else {
  loadNextMatch(); // Pravi novi AJAX call
}

// SADA:
if (response.data.progress) {
  votedMatches = response.data.progress.voted;
  totalMatches = response.data.progress.total;
  updateProgressBar();
}

if (response.data.next_match) {
  loadNextMatch(response.data.next_match); // Prosledi match data direktno
} else {
  showStageComplete(); // Novi screen
}
```

**Linija ~146+: loadNextMatch funkcija**

```javascript
// BILO:
function loadNextMatch() {
  $.ajax({
    action: "yuv_get_next_match",
    // ... pravi novi request
  });
}

// SADA:
function loadNextMatch(matchData) {
  if (!matchData) return;

  // Prima matchData direktno, NE pravi novi AJAX call
  currentMatchId = matchData.match_id;
  endTime = matchData.end_time;

  // ... animacije ...
  updateArenaContent(matchData);
}
```

**Linija ~195+: updateArenaContent funkcija**

```javascript
// BILO:
const item1 = data.item1;
const item2 = data.item2;
$(".yuv-arena-header h2").text(`OSMINA FINALA ${data.match_number || ""}`);

// SADA:
const contenders = data.contenders; // Nova struktura
const item1 = contenders[0];
const item2 = contenders[1];

// Stage name mapping
const stageNames = {
  of: "OSMINA FINALA",
  qf: "ČETVRTFINALE",
  sf: "POLUFINALE",
  final: "FINALE",
};
const stageName = stageNames[data.stage] || "DUEL";
$(".yuv-arena-header h2").text(`${stageName} ${data.match_number || ""}`);
```

#### DODATO:

**Linija ~252+: Nova funkcija za završetak stage-a**

```javascript
function showStageComplete() {
  arena.html(`
    <div class="yuv-stage-complete">
      <div class="yuv-complete-icon">✓</div>
      <h2>Završili ste sve mečeve u ovoj fazi!</h2>
      <p>Vratite se kasnije za sledeću rundu.</p>
      <a href="${window.location.origin}" class="yuv-btn-primary">Nazad na početnu</a>
    </div>
  `);
}
```

---

### 3. **inc/voting/tournament/shortcodes/bracket-shortcode.php**

#### IZMENJENO:

**Linija ~448: Dodato data-voted-matches atribut**

```php
// BILO:
<div class="yuv-duel-arena"
     data-tournament-id="<?php echo esc_attr($tournament_id); ?>"
     data-match-id="<?php echo esc_attr($match_id); ?>"
     data-stage="<?php echo esc_attr($stage); ?>"
     data-total-matches="<?php echo esc_attr($total_stage_matches); ?>"
     data-user-voted="<?php echo $has_voted ? 'true' : 'false'; ?>"
     ...>

// SADA:
<div class="yuv-duel-arena"
     data-tournament-id="<?php echo esc_attr($tournament_id); ?>"
     data-match-id="<?php echo esc_attr($match_id); ?>"
     data-stage="<?php echo esc_attr($stage); ?>"
     data-total-matches="<?php echo esc_attr($total_stage_matches); ?>"
     data-voted-matches="<?php echo esc_attr($user_votes_in_stage); ?>"  <!-- NOVO -->
     data-user-voted="<?php echo $has_voted ? 'true' : 'false'; ?>"
     ...>
```

**Napomena**: Promenljiva `$user_votes_in_stage` već postoji u fajlu (linija ~410+), računa se preko SQL query-a.

**Linija ~477-483: Progress bar inicijalizacija**

```php
// BILO:
<div class="yuv-progress-fill" style="width: 0%"></div>
<div class="yuv-progress-text">0/<?php echo esc_html($total_stage_matches); ?> duels completed</div>

// SADA:
<?php
$progress_percent = $total_stage_matches > 0
  ? ($user_votes_in_stage / $total_stage_matches) * 100
  : 0;
?>
<div class="yuv-progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%"></div>
<div class="yuv-progress-text"><?php echo esc_html($user_votes_in_stage); ?>/<?php echo esc_html($total_stage_matches); ?> duels completed</div>
```

---

### 4. **css/tournament-carousel.css**

#### DODATO:

**Linija ~160+: Stage complete screen stilovi**

```css
/* ========================================================================
   STAGE COMPLETE SCREEN
   ======================================================================== */

.yuv-stage-complete {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
  min-height: 500px;
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  border-radius: 20px;
  animation: fadeInScale 0.6s ease-out;
}

.yuv-complete-icon {
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 60px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 50%;
  margin-bottom: 30px;
  color: white;
  box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
  animation: checkBounce 0.8s ease-out 0.3s both;
}

@keyframes checkBounce {
  0% {
    transform: scale(0) rotate(-180deg);
    opacity: 0;
  }
  50% {
    transform: scale(1.2) rotate(10deg);
  }
  100% {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

.yuv-stage-complete h2 {
  font-size: 32px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 15px 0;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.yuv-stage-complete p {
  font-size: 18px;
  color: #a1a1a1;
  margin: 0 0 40px 0;
}

.yuv-btn-primary {
  display: inline-block;
  padding: 16px 40px;
  background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
  color: white;
  text-decoration: none;
  border-radius: 50px;
  font-weight: 700;
  font-size: 16px;
  text-transform: uppercase;
  letter-spacing: 1px;
  transition: all 0.3s ease;
  box-shadow: 0 5px 20px rgba(255, 107, 53, 0.4);
}

.yuv-btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 30px rgba(255, 107, 53, 0.6);
  color: white;
  text-decoration: none;
}
```

---

## 🔄 KAKO TREBA DA RADI (Flow)

### 1. **Page Load**

```
User → Otvara tournament page
       ↓
Shortcode → SQL query za $user_votes_in_stage
          → Prosleđuje kao data-voted-matches="X"
          → Progress bar inicijalizovan sa X/Y
       ↓
JavaScript → Čita arena.data("voted-matches")
           → Inicijalizuje votedMatches varijablu
```

### 2. **Vote Click**

```
User → Klikne "GLASAJ" button
     ↓
JavaScript → AJAX call: yuv_cast_tournament_vote
           → Šalje: match_id, item_id
     ↓
PHP → Insert u voting_list_votes tabelu
    → Poziva yuv_calculate_stage_progress()
    → Poziva yuv_find_next_unvoted_match()
    → Poziva yuv_get_match_data() za next match
    → Vraća: {next_match: {...}, progress: {...}}
     ↓
JavaScript → Update progress bar
           → Winner/loser animacije
           → if (next_match) → loadNextMatch(next_match)
           → else → showStageComplete()
```

### 3. **Load Next Match**

```
JavaScript → loadNextMatch(matchData) prima kompletan objekat
           → Slide out animacija
           → updateArenaContent(matchData)
           → Update slike, imena, opise
           → Slide in animacija
           → Restart timer
```

### 4. **Stage Complete**

```
Server → Vraća next_match: null
       ↓
JavaScript → showStageComplete()
           → Prikazuje zeleni checkmark
           → "Završili ste sve mečeve..."
           → Button "Nazad na početnu"
```

---

## 📊 DATABASE STRUKTURA

### Tabela: `voting_list_votes`

```sql
CREATE TABLE voting_list_votes (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  voting_list_id BIGINT NOT NULL,    -- Match ID (post_id)
  voting_item_id BIGINT NOT NULL,    -- Contestant ID koji je dobio glas
  user_id BIGINT NOT NULL,           -- 0 za guest, >0 za logged-in
  ip_address VARCHAR(45),            -- Za guest tracking
  vote_value INT DEFAULT 1,
  created_at DATETIME
);
```

### Key Queries:

**Provera da li je user glasao:**

```sql
SELECT COUNT(*)
FROM voting_list_votes
WHERE voting_list_id = %d
  AND (user_id = %d OR (user_id = 0 AND ip_address = %s))
```

**Broj glasanih mečeva u stage:**

```sql
SELECT COUNT(DISTINCT v.voting_list_id)
FROM voting_list_votes v
INNER JOIN postmeta pm1 ON v.voting_list_id = pm1.post_id AND pm1.meta_key = '_yuv_stage'
INNER JOIN postmeta pm2 ON v.voting_list_id = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
WHERE v.user_id = %d
  AND pm1.meta_value = %s
  AND pm2.meta_value = %d
```

**Pronalaženje prvog neglasanog meča:**

```sql
SELECT p.ID
FROM posts p
INNER JOIN postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_yuv_stage'
INNER JOIN postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
LEFT JOIN voting_list_votes v ON p.ID = v.voting_list_id AND v.user_id = %d
WHERE p.post_type = 'voting_list'
  AND pm1.meta_value = %s
  AND pm2.meta_value = %d
  AND v.id IS NULL
ORDER BY pm4.meta_value ASC
LIMIT 1
```

---

## 🐛 ŠTA MOŽE BITI PROBLEM (Debug Checklist)

### Backend Issues:

- [ ] **Helper funkcije nisu vidljive** - proveri da li su dodane u `tournament-ajax.php`
- [ ] **SQL errors** - proveri DB log za syntax greške
- [ ] **Meta fields nedostaju** - proveri da li match ima `_yuv_tournament_id`, `_yuv_stage`
- [ ] **\_voting_items nije array** - proveri strukturu meta fielda
- [ ] **User ID tracking** - proveri da li `get_current_user_id()` radi
- [ ] **IP tracking** - proveri da li `$_SERVER['REMOTE_ADDR']` vraća IP

### Frontend Issues:

- [ ] **`.yuv-duel-arena` ne postoji** - proveri HTML strukturu
- [ ] **`data-voted-matches` nedostaje** - proveri shortcode output
- [ ] **AJAX response format** - otvori DevTools Network tab, proveri response
- [ ] **JavaScript greške** - otvori Console, proveri errore
- [ ] **Selektori ne rade** - proveri da li su class names tačni

### Response Structure:

```javascript
// Očekivani AJAX response:
{
  success: true,
  data: {
    message: "Glas uspešno zabeležen!",
    vote_id: 123,
    next_match: {
      match_id: 456,
      stage: "of",
      match_number: 3,
      end_time: 1735324800,
      contenders: [
        {id: 1, name: "...", description: "...", image: "..."},
        {id: 2, name: "...", description: "...", image: "..."}
      ]
    },
    progress: {
      total: 8,
      voted: 3,
      remaining: 5,
      percent: 37
    }
  }
}

// Kada nema više mečeva:
{
  success: true,
  data: {
    message: "Glas uspešno zabeležen!",
    vote_id: 123,
    next_match: null,  // <-- KLJUČNO
    progress: {
      total: 8,
      voted: 8,
      remaining: 0,
      percent: 100
    }
  }
}
```

---

## 🧪 TESTING CHECKLIST

### Testiranje kao logged-in user:

1. [ ] Otvori tournament page
2. [ ] Proveri da progress bar pokazuje tačan broj (DB driven)
3. [ ] Glasaj za contestant
4. [ ] Proveri da se progress bar update-uje
5. [ ] Proveri da se pojavi sledeći match automatski
6. [ ] Završi sve mečeve
7. [ ] Proveri da se pojavi "Stage Complete" screen
8. [ ] Refresh page - progress bar treba ostati isti (persistent!)

### Testiranje kao guest (logout):

1. [ ] Otvori Incognito/Private mode
2. [ ] Ponovi sve korake iznad
3. [ ] Proveri da se tracking vrši preko IP adrese

### Cross-device test:

1. [ ] Glasaj sa računara (login)
2. [ ] Otvori isti user na telefonu
3. [ ] Proveri da progress bar pokazuje iste glasove

### Edge cases:

1. [ ] Šta se dešava ako match nema \_voting_items?
2. [ ] Šta se dešava ako contenders array je prazan?
3. [ ] Šta se dešava ako je stage već završen?
4. [ ] Može li user glasati duplo? (treba da bude onemogućeno)

---

## 📝 NOTES

- **Backup**: Napravljen pre refaktoringa
- **Git commit**: Sve izmene commitovane zajedno
- **Backward compatibility**: `showFinalBracket()` funkcija ostavljena u JS (deprecated ali ne uklonjena)
- **No breaking changes**: Stare funkcionalnosti ostaju, samo se dodaje nova logika

---

## 🎯 EXPECTED OUTCOME

- ✅ Progress se pamti u bazi
- ✅ Cross-device sync
- ✅ Nema localStorage dependency
- ✅ Bolji UX - manje AJAX callova
- ✅ Stage complete screen umesto redirect na bracket
- ✅ Centralizovana kontrola nad podacima

---

**Status**: ✅ Implementirano, čeka testiranje  
**Priority**: 🔴 HIGH - treba testirati pre push na production  
**Assigned to**: Debugging agent
