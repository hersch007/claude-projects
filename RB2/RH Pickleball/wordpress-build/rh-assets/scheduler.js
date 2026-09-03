/* Pickleball Tournament Scheduler 2 - front-end app (self-contained, no backend)
 * SPDX-License-Identifier: MIT  |  Copyright (c) 2026 Rock Hill Pickleball Club */
(function(){
  "use strict";
  var root=document.getElementById("pbts-root");
  if(!root)return;
  var LS_KEY="pbts_scheduler_v2";
  var $=function(id){return document.getElementById("pbts-"+id);};
  var qa=function(sel,ctx){return Array.prototype.slice.call((ctx||root).querySelectorAll(sel));};
  var state=null, currentFmt="rr";

  /* ================= SETUP ================= */
  (function init(){
    var nt=$("numTeams");
    for(var t=4;t<=12;t++){var o=document.createElement("option");o.value=t;o.textContent=t+" teams";if(t===6)o.selected=true;nt.appendChild(o);}
    var nc=$("numCourts");
    for(var c=1;c<=8;c++){var o2=document.createElement("option");o2.value=c;o2.textContent=c+(c===1?" court":" courts");if(c===3)o2.selected=true;nc.appendChild(o2);}
    var np=$("numPools");
    for(var p=2;p<=6;p++){var o3=document.createElement("option");o3.value=p;o3.textContent=p+" pools";if(p===2)o3.selected=true;np.appendChild(o3);}
    var av=$("advance");
    for(var a=1;a<=4;a++){var o4=document.createElement("option");o4.value=a;o4.textContent="top "+a;if(a===2)o4.selected=true;av.appendChild(o4);}

    qa("button",$("fmt")).forEach(function(b){
      b.addEventListener("click",function(){
        qa("button",$("fmt")).forEach(function(x){x.classList.remove("pbts-active");});
        b.classList.add("pbts-active");
        currentFmt=b.getAttribute("data-fmt");
        $("poolOpts").style.display=(currentFmt==="pool")?"flex":"none";
        updateHint();
      });
    });
    $("numTeams").addEventListener("change",renderTeamInputs);
    $("perTeam").addEventListener("change",updateHint);
    $("numPools").addEventListener("change",updateHint);
    $("advance").addEventListener("change",updateHint);
    renderTeamInputs();
  })();

  function updateHint(){
    var teams=+$("numTeams").value, per=+$("perTeam").value;
    if(currentFmt==="rr"){
      var rounds=(teams%2===0)?teams-1:teams, perRound=Math.floor(teams/2);
      $("summaryHint").textContent=(teams*per)+" players • "+rounds+" rounds • "+perRound+" match"+(perRound===1?"":"es")+" per round • "+(teams*(teams-1)/2)+" total matches";
      $("modeHelp").textContent="Round-robin: every team plays every other team once. Odd number of teams? One sits out (bye) each round automatically.";
    }else{
      var pools=+$("numPools").value, adv=+$("advance").value;
      var minSize=Math.floor(teams/pools), maxSize=Math.ceil(teams/pools);
      var qual=Math.min(adv,minSize)*pools;
      $("summaryHint").textContent=(teams*per)+" players • "+pools+" pools of "+(minSize===maxSize?minSize:minSize+"–"+maxSize)+" • top "+adv+" advance • "+qual+" teams make the playoff bracket";
      $("modeHelp").textContent="Each pool plays a mini round-robin. Enter pool scores, then seed a single-elimination playoff bracket from the standings.";
    }
  }

  function renderTeamInputs(){
    var teams=+$("numTeams").value, box=$("teamInputs"), existing={};
    qa(".pbts-teamcard",box).forEach(function(card){
      var i=card.getAttribute("data-i");
      existing[i]={name:card.querySelector(".pbts-t-name").value,players:card.querySelector(".pbts-t-players").value};
    });
    box.innerHTML="";
    for(var i=0;i<teams;i++){
      var prev=existing[i]||{};
      var card=document.createElement("div");card.className="pbts-teamcard";card.setAttribute("data-i",i);
      card.innerHTML='<div class="pbts-th"><span class="pbts-num">'+(i+1)+'</span>'+
        '<input class="pbts-t-name" type="text" placeholder="Team '+(i+1)+' name" value="'+(prev.name!=null?escAttr(prev.name):"")+'"></div>'+
        '<input class="pbts-t-players" type="text" placeholder="Players (comma separated) - optional" value="'+(prev.players!=null?escAttr(prev.players):"")+'">';
      box.appendChild(card);
    }
    updateHint();
  }
  function escAttr(s){return String(s).replace(/"/g,"&quot;").replace(/</g,"&lt;");}
  function esc(s){return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");}

  /* ============== ROUND ROBIN CORE ============== */
  function buildRounds(n){
    var idx=[];for(var i=0;i<n;i++)idx.push(i);
    if(idx.length%2!==0)idx.push(-1);
    var m=idx.length,half=m/2,total=m-1,fixed=idx[0],rot=idx.slice(1),rounds=[],byes=[];
    for(var r=0;r<total;r++){
      var arr=[fixed].concat(rot),matches=[],bye=null;
      for(var k=0;k<half;k++){
        var a=arr[k],b=arr[m-1-k];
        if(a===-1)bye=b; else if(b===-1)bye=a; else matches.push({a:a,b:b});
      }
      rounds.push(matches);byes.push(bye);
      rot.unshift(rot.pop());
    }
    return {rounds:rounds,byes:byes};
  }

  function readTeams(){
    var teams=[];
    qa(".pbts-teamcard",$("teamInputs")).forEach(function(card,i){
      teams.push({name:card.querySelector(".pbts-t-name").value.trim()||("Team "+(i+1)),
        players:card.querySelector(".pbts-t-players").value.trim()});
    });
    return teams;
  }

  /* ============== GENERATE ============== */
  function generate(){
    var teams=readTeams(), courts=+$("numCourts").value;
    var base={teams:teams,perTeam:+$("perTeam").value,courts:courts,target:$("target").value,format:currentFmt};

    if(currentFmt==="rr"){
      var rr=buildRounds(teams.length);
      base.rounds=rr.rounds.map(function(ms){
        return ms.map(function(m,i){return {a:m.a,b:m.b,court:(i%courts)+1,wave:Math.floor(i/courts),sa:"",sb:""};});
      });
      base.byes=rr.byes;
    }else{
      var pools=+$("numPools").value, adv=+$("advance").value;
      var minSize=Math.floor(teams.length/pools);
      if(minSize<2){alert("Too many pools - each pool needs at least 2 teams. Reduce the number of pools.");return;}
      if(adv>minSize){alert("'Advance per pool' can't exceed your smallest pool size ("+minSize+"). Lower it.");return;}
      if(adv*pools<2){alert("Need at least 2 teams advancing to make a bracket.");return;}
      var pl=[];for(var p=0;p<pools;p++)pl.push([]);
      teams.forEach(function(t,i){
        var band=Math.floor(i/pools), pos=i%pools;
        var pool=(band%2===0)?pos:(pools-1-pos);
        pl[pool].push(i);
      });
      var scheds=pl.map(function(pool){return buildRounds(pool.length);});
      var maxR=0;scheds.forEach(function(s){if(s.rounds.length>maxR)maxR=s.rounds.length;});
      var combined=[], poolByes=[];
      for(var r=0;r<maxR;r++){
        var slot=[], byesThis=[];
        scheds.forEach(function(s,pi){
          var pr=s.rounds[r];
          if(pr)pr.forEach(function(m){slot.push({pool:pi,a:pl[pi][m.a],b:pl[pi][m.b]});});
          var by=s.byes[r];
          if(by!=null&&by!==-1)byesThis.push({pool:pi,team:pl[pi][by]});
        });
        slot.forEach(function(m,i){m.court=(i%courts)+1;m.wave=Math.floor(i/courts);m.sa="";m.sb="";});
        combined.push(slot);poolByes.push(byesThis);
      }
      base.numPools=pools;base.advance=adv;base.pools=pl;
      base.poolRounds=combined;base.poolByes=poolByes;base.bracket=null;
    }
    state=base;save();showResults();
  }

  /* ============== RESULTS SHELL ============== */
  function showResults(){
    $("setup").classList.add("pbts-hidden");
    $("results").classList.remove("pbts-hidden");
    var tabs=$("tabs");tabs.innerHTML="";
    var defs=(state.format==="rr")
      ? [["a","📅 Schedule"],["b","🏆 Standings"]]
      : [["a","🎱 Pool Play"],["b","🏆 Playoffs"]];
    defs.forEach(function(d,i){
      var btn=document.createElement("button");btn.className="pbts-tab"+(i===0?" pbts-active":"");
      btn.textContent=d[1];btn.setAttribute("data-tab",d[0]);
      btn.addEventListener("click",function(){
        qa(".pbts-tab",tabs).forEach(function(t){t.classList.remove("pbts-active");});
        btn.classList.add("pbts-active");
        $("tab-a").classList.toggle("pbts-hidden",d[0]!=="a");
        $("tab-b").classList.toggle("pbts-hidden",d[0]!=="b");
      });
      tabs.appendChild(btn);
    });
    $("tab-a").classList.remove("pbts-hidden");$("tab-b").classList.add("pbts-hidden");
    if(state.format==="rr"){renderRR();}else{renderPools();renderPlayoffTab();}
    if(root.scrollIntoView)root.scrollIntoView({behavior:"smooth",block:"start"});
  }

  /* ============== RENDER: ROUND ROBIN ============== */
  function renderRR(){
    var host=$("tab-a");host.innerHTML="";var wrapEl=document.createElement("div");
    state.rounds.forEach(function(matches,r){
      var div=document.createElement("div");div.className="pbts-round";
      var h='<h3><span class="pbts-pill">Round '+(r+1)+'</span></h3>';
      var by=state.byes[r];
      if(by!=null&&by!==-1)h+='<div class="pbts-bye">😴 <strong>'+esc(state.teams[by].name)+'</strong> has a bye this round</div>';
      div.innerHTML=h;
      matches.forEach(function(m){div.appendChild(matchEl(m,null,renderStandingsRR));});
      wrapEl.appendChild(div);
    });
    host.appendChild(wrapEl);
    renderStandingsRR();
  }
  function renderStandingsRR(){
    var rows=standingsFor(state.teams.map(function(_,i){return i;}),allMatches());
    $("tab-b").innerHTML=standingsTable(rows,null);
  }
  function allMatches(){var out=[];state.rounds.forEach(function(ms){ms.forEach(function(m){out.push(m);});});return out;}

  /* ============== RENDER: POOLS ============== */
  function renderPools(){
    var host=$("tab-a");host.innerHTML="";
    var sched=document.createElement("div");
    state.poolRounds.forEach(function(matches,r){
      var div=document.createElement("div");div.className="pbts-round";
      div.innerHTML='<h3><span class="pbts-pill">Round '+(r+1)+'</span></h3>';
      state.poolByes[r].forEach(function(b){
        var d=document.createElement("div");d.className="pbts-bye";
        d.innerHTML='😴 <strong>'+esc(state.teams[b.team].name)+'</strong> (Pool '+poolLetter(b.pool)+') has a bye';
        div.appendChild(d);
      });
      matches.forEach(function(m){div.appendChild(matchEl(m,poolLetter(m.pool),renderPoolStandings));});
      sched.appendChild(div);
    });
    host.appendChild(sched);
    var stand=document.createElement("div");stand.id="pbts-poolStandings";
    host.appendChild(stand);
    renderPoolStandings();
  }
  function renderPoolStandings(){
    var host=$("poolStandings");if(!host)return;host.innerHTML="";
    var title=document.createElement("h2");title.style.marginTop="24px";
    title.innerHTML='Pool Standings <span class="pbts-tag">top '+state.advance+' advance</span>';
    host.appendChild(title);
    state.pools.forEach(function(pool,p){
      var block=document.createElement("div");block.className="pbts-poolblock";
      block.innerHTML='<h3><span class="pbts-pill">Pool '+poolLetter(p)+'</span></h3>';
      var rows=standingsFor(pool,poolMatches(p));
      block.innerHTML+=standingsTable(rows,state.advance);
      host.appendChild(block);
    });
  }
  function poolMatches(p){var out=[];state.poolRounds.forEach(function(ms){ms.forEach(function(m){if(m.pool===p)out.push(m);});});return out;}
  function poolLetter(p){return String.fromCharCode(65+p);}

  /* ============== SHARED: match element + standings ============== */
  function matchEl(m,poolLbl,onScore){
    var A=state.teams[m.a],B=state.teams[m.b];
    var el=document.createElement("div");el.className="pbts-match";applyWin(el,m);
    var courtCls="pbts-court"+(m.wave>0?" pbts-wave":"");
    var courtLbl="Court "+m.court+(m.wave>0?" · Wave "+(m.wave+1):"");
    var poolHtml=poolLbl?'<span class="pbts-pooltag">Pool '+poolLbl+'</span>':'';
    el.innerHTML=poolHtml+'<span class="'+courtCls+'">'+courtLbl+'</span>'+
      '<div class="pbts-side pbts-left"><div class="pbts-tname">'+esc(A.name)+(A.players?'<small>'+esc(A.players)+'</small>':'')+'</div></div>'+
      '<input class="pbts-score pbts-sa" type="number" min="0" inputmode="numeric" placeholder="–" value="'+esc(m.sa)+'">'+
      '<span class="pbts-vs">vs</span>'+
      '<input class="pbts-score pbts-sb" type="number" min="0" inputmode="numeric" placeholder="–" value="'+esc(m.sb)+'">'+
      '<div class="pbts-side pbts-right"><div class="pbts-tname">'+esc(B.name)+(B.players?'<small>'+esc(B.players)+'</small>':'')+'</div></div>';
    var sa=el.querySelector(".pbts-sa"),sb=el.querySelector(".pbts-sb");
    function upd(){m.sa=sa.value;m.sb=sb.value;applyWin(el,m);save();if(onScore)onScore();}
    sa.addEventListener("input",upd);sb.addEventListener("input",upd);
    return el;
  }
  function applyWin(el,m){
    el.classList.remove("pbts-done-a","pbts-done-b");
    if(m.sa!==""&&m.sb!==""){var a=+m.sa,b=+m.sb;if(a>b)el.classList.add("pbts-done-a");else if(b>a)el.classList.add("pbts-done-b");}
  }
  function standingsFor(teamIdxList,matches){
    var rows={};teamIdxList.forEach(function(i){rows[i]={i:i,name:state.teams[i].name,gp:0,w:0,l:0,pf:0,pa:0};});
    matches.forEach(function(m){
      if(m.sa===""||m.sb==="")return;if(rows[m.a]==null||rows[m.b]==null)return;
      var a=+m.sa,b=+m.sb,ra=rows[m.a],rb=rows[m.b];
      ra.gp++;rb.gp++;ra.pf+=a;ra.pa+=b;rb.pf+=b;rb.pa+=a;
      if(a>b){ra.w++;rb.l++;}else if(b>a){rb.w++;ra.l++;}
    });
    var arr=teamIdxList.map(function(i){return rows[i];});
    arr.sort(function(x,y){return (y.w-x.w)||((y.pf-y.pa)-(x.pf-x.pa))||(y.pf-x.pf);});
    return arr;
  }
  function standingsTable(rows,advanceLine){
    var played=rows.some(function(r){return r.gp>0;});
    if(!played)return '<p class="pbts-empty">Enter match scores and standings build automatically. 🏓</p>';
    var h='<table><thead><tr><th>#</th><th class="pbts-team-cell">Team</th><th>GP</th><th>W</th><th>L</th><th>PF</th><th>PA</th><th>Diff</th></tr></thead><tbody>';
    rows.forEach(function(r,idx){
      var diff=r.pf-r.pa, adv=(advanceLine!=null&&idx<advanceLine);
      var cls=adv?' class="pbts-qualifies"':'';
      var ln=(advanceLine!=null&&idx===advanceLine-1)?' pbts-adv-line':'';
      h+='<tr'+cls+'><td class="pbts-rank'+ln+'">'+(idx+1)+'</td>'+
        '<td class="pbts-team-cell'+ln+'">'+esc(r.name)+(adv?' ✔':'')+'</td>'+
        '<td class="'+ln.trim()+'">'+r.gp+'</td><td class="'+ln.trim()+'">'+r.w+'</td><td class="'+ln.trim()+'">'+r.l+'</td>'+
        '<td class="'+ln.trim()+'">'+r.pf+'</td><td class="'+ln.trim()+'">'+r.pa+'</td>'+
        '<td class="'+ln.trim()+'">'+(diff>0?"+":"")+diff+'</td></tr>';
    });
    return h+'</tbody></table>';
  }

  /* ============== PLAYOFF BRACKET ============== */
  function renderPlayoffTab(){
    var host=$("tab-b");host.innerHTML="";
    var wrapEl=document.createElement("div");wrapEl.className="pbts-seedbtn-wrap";
    var btn=document.createElement("button");btn.className="pbts-btn pbts-btn-primary";btn.style.marginTop="0";
    btn.textContent=state.bracket?"↻ Re-seed Bracket from Current Standings":"🏁 Seed Playoff Bracket from Pool Standings";
    btn.addEventListener("click",seedBracket);
    wrapEl.appendChild(btn);
    var note=document.createElement("p");note.className="pbts-hint";
    note.textContent="Seeds are set by pool finish (all pool winners first, then runners-up…), ranked by record. Tap the winner of each match to advance them.";
    wrapEl.appendChild(note);
    host.appendChild(wrapEl);
    var bh=document.createElement("div");bh.id="pbts-bracketHost";host.appendChild(bh);
    if(state.bracket)renderBracket();
    else bh.innerHTML='<p class="pbts-empty">Finish (or partly finish) pool play, then seed the bracket above. 🏆</p>';
  }

  function nextPow2(n){var p=1;while(p<n)p*=2;return p;}
  function seedOrder(P){
    var seeds=[1,2],rounds=Math.round(Math.log(P)/Math.log(2));
    for(var r=1;r<rounds;r++){
      var out=[],sum=Math.pow(2,r+1)+1;
      for(var i=0;i<seeds.length;i++){out.push(seeds[i]);out.push(sum-seeds[i]);}
      seeds=out;
    }
    return seeds;
  }
  function seedBracket(){
    var ranked=state.pools.map(function(pool,p){return standingsFor(pool,poolMatches(p));});
    var anyPlayed=ranked.some(function(rk){return rk.some(function(r){return r.gp>0;});});
    if(!anyPlayed){alert("Enter some pool scores first so the bracket can be seeded.");return;}
    if(state.bracket&&!confirm("Re-seed the bracket from current standings? This clears any playoff results you've entered."))return;
    var seeds=[];
    for(var rank=0;rank<state.advance;rank++){
      var tier=[];
      ranked.forEach(function(rk){if(rk[rank])tier.push(rk[rank]);});
      tier.sort(function(x,y){return (y.w-x.w)||((y.pf-y.pa)-(x.pf-x.pa))||(y.pf-x.pf);});
      tier.forEach(function(r){seeds.push(r.i);});
    }
    var K=seeds.length,P=nextPow2(K),order=seedOrder(P);
    var slots=order.map(function(sn){return sn<=K?seeds[sn-1]:"BYE";});
    var numRounds=Math.round(Math.log(P)/Math.log(2));
    var picks=[];for(var r=0;r<numRounds;r++){var cnt=P/Math.pow(2,r+1),a=[];for(var m=0;m<cnt;m++)a.push(null);picks.push(a);}
    state.bracket={slots:slots,seeds:seeds,picks:picks,numRounds:numRounds};
    save();renderPlayoffTab();
  }

  function slotTeam(r,m,s){
    if(r===0)return state.bracket.slots[2*m+s];
    return resolvedWinner(r-1,2*m+s);
  }
  function resolvedWinner(r,m){
    var a=slotTeam(r,m,0),b=slotTeam(r,m,1);
    if(a==="BYE"&&b!=null&&b!=="BYE")return b;
    if(b==="BYE"&&a!=null&&a!=="BYE")return a;
    if(a==="BYE"&&b==="BYE")return "BYE";
    var w=state.bracket.picks[r][m];
    if(w==null)return null;
    var t=(w===0?a:b);
    return (t==null||t==="BYE")?null:t;
  }
  function renderBracket(){
    var b=state.bracket,P=b.slots.length,host=$("bracketHost");host.innerHTML="";
    var seedNum={};b.seeds.forEach(function(team,i){seedNum[team]=i+1;});
    var wrapEl=document.createElement("div");wrapEl.className="pbts-bracket";
    for(var r=0;r<b.numRounds;r++){
      var cnt=P/Math.pow(2,r+1);
      var col=document.createElement("div");col.className="pbts-bcol";
      col.innerHTML='<h4>'+roundName(cnt)+'</h4>';
      for(var m=0;m<cnt;m++)col.appendChild(bMatch(r,m,seedNum));
      wrapEl.appendChild(col);
    }
    host.appendChild(wrapEl);
    var champ=resolvedWinner(b.numRounds-1,0);
    if(champ!=null&&champ!=="BYE"){
      var c=document.createElement("div");c.className="pbts-champion";
      c.innerHTML='🏆 Champion: '+esc(state.teams[champ].name);
      host.appendChild(c);
    }
  }
  function roundName(matches){
    if(matches===1)return "Final";
    if(matches===2)return "Semifinals";
    if(matches===4)return "Quarterfinals";
    return "Round of "+(matches*2);
  }
  function bMatch(r,m,seedNum){
    var wrap=document.createElement("div");wrap.className="pbts-bmatch";
    [0,1].forEach(function(s){
      var team=slotTeam(r,m,s);
      var row=document.createElement("div");row.className="pbts-bteam";
      var win=(resolvedWinner(r,m)!=null && resolvedWinner(r,m)===team && team!=="BYE" && team!=null);
      if(team==null){row.className+=" pbts-tbd";row.innerHTML='<span>TBD</span>';}
      else if(team==="BYE"){row.className+=" pbts-bye";row.innerHTML='<span>Bye</span>';}
      else{
        if(win)row.className+=" pbts-win";
        var sn=(r===0&&seedNum[team])?'<span class="pbts-bseed">#'+seedNum[team]+'</span>':'';
        row.innerHTML='<span>'+esc(state.teams[team].name)+'</span>'+sn;
        row.addEventListener("click",function(){
          var other=slotTeam(r,m,s===0?1:0);
          if(other==null||other==="BYE")return;
          state.bracket.picks[r][m]=s;save();renderBracket();
        });
      }
      wrap.appendChild(row);
    });
    return wrap;
  }

  /* ============== PERSIST + BUTTONS ============== */
  function save(){try{localStorage.setItem(LS_KEY,JSON.stringify(state));}catch(e){}}
  function load(){try{var s=localStorage.getItem(LS_KEY);return s?JSON.parse(s):null;}catch(e){return null;}}

  $("genBtn").addEventListener("click",generate);
  $("printBtn").addEventListener("click",function(){window.print();});
  $("editBtn").addEventListener("click",function(){$("results").classList.add("pbts-hidden");$("setup").classList.remove("pbts-hidden");});
  $("resetBtn").addEventListener("click",function(){
    if(!confirm("Start a new tournament? This clears the current schedule and all scores."))return;
    try{localStorage.removeItem(LS_KEY);}catch(e){}
    state=null;$("results").classList.add("pbts-hidden");$("setup").classList.remove("pbts-hidden");
  });

  /* ============== RESTORE ============== */
  (function restore(){
    var s=load();if(!s)return;state=s;currentFmt=s.format;
    qa("button",$("fmt")).forEach(function(b){b.classList.toggle("pbts-active",b.getAttribute("data-fmt")===currentFmt);});
    $("poolOpts").style.display=(currentFmt==="pool")?"flex":"none";
    $("numTeams").value=s.teams.length;$("perTeam").value=s.perTeam;$("numCourts").value=s.courts;$("target").value=s.target;
    if(currentFmt==="pool"){$("numPools").value=s.numPools;$("advance").value=s.advance;}
    renderTeamInputs();
    qa(".pbts-teamcard",$("teamInputs")).forEach(function(card,i){
      if(s.teams[i]){card.querySelector(".pbts-t-name").value=s.teams[i].name;card.querySelector(".pbts-t-players").value=s.teams[i].players||"";}
    });
    showResults();
  })();
})();
