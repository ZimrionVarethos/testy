Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = 'rgba(17,24,39,0.35)';

const rpLabels    = window.ReportData.labels;
const rpCompleted = window.ReportData.completed;
const rpCancelled = window.ReportData.cancelled;
const rpRevenue   = window.ReportData.revenue;
const rpStatus    = window.ReportData.status;
const rpFleet     = window.ReportData.fleet;

(function(){
    const ctx = document.getElementById('rpBookingChart').getContext('2d');
    new Chart(ctx,{ type:'bar', data:{ labels:rpLabels, datasets:[
        { label:'Selesai',    data:rpCompleted, backgroundColor:'rgba(17,24,39,0.75)', borderRadius:4, borderSkipped:false },
        { label:'Dibatalkan', data:rpCancelled, backgroundColor:'rgba(220,38,38,0.18)', borderRadius:4, borderSkipped:false },
    ]}, options:{ responsive:true, maintainAspectRatio:true,
        plugins:{ legend:{ display:true, position:'top', labels:{font:{size:11},boxWidth:10,boxHeight:10,borderRadius:3,useBorderRadius:true,padding:12} }, tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:11},padding:10,cornerRadius:8} },
        scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10},precision:0}}, x:{grid:{display:false},ticks:{font:{size:10}}} }
    }});
})();

(function(){
    const ctx=document.getElementById('rpRevenueChart').getContext('2d');
    const g=ctx.createLinearGradient(0,0,0,200);
    g.addColorStop(0,'rgba(17,24,39,0.08)'); g.addColorStop(1,'rgba(17,24,39,0)');
    new Chart(ctx,{ type:'line', data:{ labels:rpLabels, datasets:[{ data:rpRevenue, borderColor:'rgb(17,24,39)', backgroundColor:g, borderWidth:2, fill:true, tension:0.4, pointBackgroundColor:'rgb(17,24,39)', pointBorderColor:'#fff', pointBorderWidth:2, pointRadius:3.5, pointHoverRadius:5 }]},
        options:{ responsive:true, maintainAspectRatio:true,
            plugins:{ legend:{display:false}, tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{family:"'DM Mono',monospace",size:11},padding:10,cornerRadius:8, callbacks:{label:c=>`  Rp ${c.raw.toLocaleString('id-ID')}`}} },
            scales:{ y:{beginAtZero:true,grid:{color:'rgba(17,24,39,0.05)'},ticks:{font:{size:10},callback:v=>'Rp'+(v/1000000).toFixed(0)+'jt'}}, x:{grid:{display:false},ticks:{font:{size:10}}} }
        }
    });
})();

(function(){
    new Chart(document.getElementById('rpStatusDonut').getContext('2d'),{
        type:'doughnut', data:{ labels:rpStatus.map(d=>d.label), datasets:[{data:rpStatus.map(d=>d.count),backgroundColor:rpStatus.map(d=>d.color),borderWidth:0,hoverOffset:4}]},
        options:{responsive:false,cutout:'70%',plugins:{legend:{display:false},tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:11},padding:10,cornerRadius:8}}}
    });
    new Chart(document.getElementById('rpFleetDonut').getContext('2d'),{
        type:'doughnut', data:{ labels:['Tersedia','Disewa','Maintenance'], datasets:[{data:[rpFleet.available||0,rpFleet.rented||0,rpFleet.maintenance||0],backgroundColor:['#16a34a','#2563eb','#d97706'],borderWidth:0,hoverOffset:4}]},
        options:{responsive:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{backgroundColor:'rgb(17,24,39)',bodyFont:{size:11},padding:10,cornerRadius:8}}}
    });
})();

function setPeriod(p){
    document.getElementById('periodInput').value=p;
    document.getElementById('monthSelect').style.display=p==='yearly'?'none':'';
    ['monthly','yearly'].forEach(id=>{
        const btn=document.getElementById('btn-'+id);
        btn.className=btn.className.replace('period-active','').replace('period-inactive','').trim()+' '+(id===p?'period-active':'period-inactive');
    });
}
function rpConfirmDelete(type,label,count){
    document.getElementById('rpDeleteType').value=type;
    document.getElementById('rpConfirmTitle').textContent=`Hapus ${label}?`;
    document.getElementById('rpConfirmMsg').textContent=`Akan menghapus ${count.toLocaleString('id-ID')} data secara permanen. Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('rpConfirmBg').classList.add('open');
    document.body.style.overflow='hidden';
}
function rpCloseConfirm(e){ if(e.target===document.getElementById('rpConfirmBg')) rpForceClose(); }
function rpForceClose(){ document.getElementById('rpConfirmBg').classList.remove('open'); document.body.style.overflow=''; }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') rpForceClose(); });
