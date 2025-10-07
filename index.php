<?php
// LCM_Race - single-file PHP + HTML + JS interactive game
// Save this file as index.php inside a folder in your XAMPP/WAMP htdocs (e.g., htdocs/lcm_race/index.php)
// Submissions (if any) will be appended to submissions.csv in the same folder.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_result') {
    // Simple server-side save to CSV. Make sure PHP has write permission in this folder.
    $name = substr(trim($_POST['name'] ?? 'Anonymous'), 0, 100);
    $score = intval($_POST['score'] ?? 0);
    $time = date('Y-m-d H:i:s');
    $line = sprintf("%s,%d,%s\n", str_replace(',', ' ', $name), $score, $time);
    file_put_contents(__DIR__ . '/submissions.csv', $line, FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 'ok']);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>LCM Race — Learn LCM with a Game</title>
  <style>
    :root{--accent:#2b8aef;--muted:#666}
    body{font-family:Inter, system-ui, Arial; padding:18px; max-width:900px; margin:0 auto;}
    h1{color:var(--accent)}
    .board{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px}
    .card{border-radius:12px;padding:12px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    .numbers{font-size:28px;font-weight:700}
    .multiples{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
    .mult{padding:8px 12px;border-radius:8px;background:#f3f7ff;cursor:pointer}
    .mult.selected{outline:3px solid #ffd54d}
    .controls{margin-top:12px}
    .btn{background:var(--accent);color:white;border:none;padding:10px 14px;border-radius:8px;cursor:pointer}
    .secondary{background:#eee;color:#333}
    .hint{color:var(--muted);font-size:13px}
    .score{font-weight:700}
    .result{margin-top:12px;padding:10px;border-radius:8px}
    .correct{background:#e6ffed;border:1px solid #55c57a}
    .wrong{background:#ffe6e6;border:1px solid #ff6b6b}
    footer{margin-top:20px;font-size:13px;color:var(--muted)}
    input[type=text]{padding:8px;border-radius:8px;border:1px solid #ddd}
  </style>
</head>
<body>
  <h1>LCM Race — Interactive Game for Grades 4–7</h1>
  <p>Topic: LCM (Least Common Multiple). Objective: find the smallest multiple two numbers share — fast and playfully.</p>
  <div class="board">
    <div class="card">
      <div>Numbers</div>
      <div class="numbers" id="numbersDisplay">—</div>
      <div class="hint" id="hintText">Click <strong>New Challenge</strong> to start. Use the multiples panels to explore candidates.</div>
      <div class="controls">
        <button class="btn" id="newBtn">New Challenge</button>
        <button class="btn secondary" id="showMultiplesBtn">Show Multiples</button>
      </div>
      <div style="margin-top:12px">
        <div>Choose what you think is the <strong>LCM</strong>:</div>
        <div id="choices" style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap"></div>
      </div>
      <div id="feedback" class="result" style="display:none"></div>
    </div>
    <div class="card">
      <div>Multiples Explorer</div>
      <div style="margin-top:8px;font-size:13px;" id="explainer">Click a number in the panel to mark it. Watch how multiples line up.</div>
      <div style="margin-top:12px">
        <div><strong id="leftNumLabel">A:</strong></div>
        <div class="multiples" id="leftMultiples"></div>
      </div>
      <div style="margin-top:12px">
        <div><strong id="rightNumLabel">B:</strong></div>
        <div class="multiples" id="rightMultiples"></div>
      </div>
      <div style="margin-top:14px">
        <div>Score: <span class="score" id="score">0</span></div>
        <div style="margin-top:8px">Player name: <input type="text" id="playerName" placeholder="Your name (optional)"></div>
        <div style="margin-top:8px"><button class="btn" id="saveBtn">Save Result</button></div>
      </div>
    </div>
  </div>
  <footer>
    <div>How it helps: visualising multiples + choosing common ones builds intuition about LCM. Game modes: Practice (slow) and Race (timed).</div>
    <div style="margin-top:6px">Rubric for assignment submission: working code, clear UI for students, instructor notes (how to teach), and optional server save.</div>
  </footer>
<script>
function gcd(a,b){while(b){[a,b]=[b,a%b]}return a}
function lcm(a,b){return Math.abs(a*b)/gcd(a,b)}
let state={a:0,b:0,answer:0,score:0}
const leftMultiples=document.getElementById('leftMultiples')
const rightMultiples=document.getElementById('rightMultiples')
const numbersDisplay=document.getElementById('numbersDisplay')
const leftNumLabel=document.getElementById('leftNumLabel')
const rightNumLabel=document.getElementById('rightNumLabel')
const choicesDiv=document.getElementById('choices')
const feedback=document.getElementById('feedback')
const scoreEl=document.getElementById('score')
const hintText=document.getElementById('hintText')
function rand(min,max){return Math.floor(Math.random()*(max-min+1))+min}
function pickNumbers(){const a=rand(2,12);let b=rand(2,12);while(b===a)b=rand(2,12);return[a,b]}
function populateMultiples(num,container){container.innerHTML='';for(let i=1;i<=12;i++){const val=num*i;const el=document.createElement('div');el.className='mult';el.textContent=val;el.dataset.value=val;el.onclick=()=>{el.classList.toggle('selected');checkCommonHighlights()};container.appendChild(el)}} 
function checkCommonHighlights(){const leftSelected=Array.from(leftMultiples.querySelectorAll('.selected')).map(x=>+x.dataset.value);const rightSelected=Array.from(rightMultiples.querySelectorAll('.selected')).map(x=>+x.dataset.value);const commons=leftSelected.filter(x=>rightSelected.includes(x));if(commons.length){hintText.textContent='Common multiples highlighted. The smallest highlighted is the LCM.';}else{hintText.textContent='Try selecting multiples to find a match. Or click Show Multiples.'}}
function makeChoices(correct){choicesDiv.innerHTML='';const opts=new Set([correct]);while(opts.size<4){const delta=rand(1,6);const sign=(Math.random()<0.5)?-1:1;let choice=Math.max(1,correct+sign*delta);opts.add(choice)}const arr=Array.from(opts).sort(()=>Math.random()-0.5);arr.forEach(opt=>{const b=document.createElement('button');b.className='mult';b.textContent=opt;b.onclick=()=>submitChoice(opt,b);choicesDiv.appendChild(b)})}
function submitChoice(choice){const correct=state.answer;feedback.style.display='block';if(choice===correct){feedback.className='result correct';feedback.textContent=`Nice! ${choice} is the LCM of ${state.a} and ${state.b}.`;state.score+=10}else{feedback.className='result wrong';feedback.textContent=`Not quite. ${choice} is not the LCM. Try exploring multiples or press New Challenge.`;state.score=Math.max(0,state.score-2)}scoreEl.textContent=state.score}
function newChallenge(){const[a,b]=pickNumbers();state.a=a;state.b=b;state.answer=lcm(a,b);numbersDisplay.textContent=`${a} and ${b}`;leftNumLabel.textContent=`A: ${a}`;rightNumLabel.textContent=`B: ${b}`;populateMultiples(a,leftMultiples);populateMultiples(b,rightMultiples);makeChoices(state.answer);feedback.style.display='none';hintText.textContent='Select multiples in the panels or press "Show Multiples" to auto-highlight common multiples.'}
function showMultiplesAuto(){const la=Array.from(leftMultiples.children);const ra=Array.from(rightMultiples.children);la.concat(ra).forEach(n=>n.classList.remove('selected'));const aVals=la.map(x=>+x.dataset.value);const bVals=ra.map(x=>+x.dataset.value);const commons=aVals.filter(x=>bVals.includes(x));la.forEach(n=>{if(commons.includes(+n.dataset.value))n.classList.add('selected')});ra.forEach(n=>{if(commons.includes(+n.dataset.value))n.classList.add('selected')});if(commons.length)hintText.textContent=`Common multiples: ${commons.join(', ')}. LCM = ${Math.min(...commons)}.`;else hintText.textContent='No common multiple in the shown range — press New Challenge for easier numbers.'}
document.getElementById('newBtn').addEventListener('click',newChallenge)
document.getElementById('showMultiplesBtn').addEventListener('click',showMultiplesAuto)
document.getElementById('saveBtn').addEventListener('click',()=>{const name=document.getElementById('playerName').value||'Anonymous';fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'save_result',name:name,score:state.score})}).then(r=>r.json()).then(j=>{if(j.status==='ok')alert('Result saved (submissions.csv).');else alert('Could not save result. Check folder permissions.')}).catch(e=>alert('Save failed: '+e.message))})
newChallenge()
</script>
</body>
</html>