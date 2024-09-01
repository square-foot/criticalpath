// Critical Path Calculator
// (c) Sabu Francis, April 5, 2021
//Based on algorithm shown here
//https://www.youtube.com/watch?v=4oDLMs11Exs

let AllActivities = [];

class Activity {
    constructor(nm, dur) {
        this.name = nm;
        this.duration = dur;
        this.earlyStart = -1;
        this.earlyFinish = -1;
        this.lateStart = -1;
        this.lateFinish = -1;
        this.predes = [];
        this.nexts = [];
    }

    reportUsingTemplate(Template) {
        const replacements = {
            "N": this.name,
            "D": this.duration,
            "ES": this.earlyStart,
            "EF": this.earlyFinish,
            "LS": this.lateStart,
            "LF": this.lateFinish,
            "PR": this.predes.join(",")
        };

        return Object.entries(replacements).reduce((str, [key, val]) => 
            str.replace(`[${key}]`, val), Template);
    }

    isOnCP() {
        return this.lateFinish === this.earlyFinish;
    }

    getName() {
        return this.name;
    }

    addPre(pre) {
        this.predes.push(pre);
    }

    addNext(nxt) {
        this.nexts.push(nxt);
    }

    countPre() {
        return this.predes.length;
    }

    setEarly() {
        if (this.earlyFinish !== -1) return;
        if (this.earlyStart === -1) {
            this.predes.forEach(objNm => getActivityObj(objNm).setEarly());
        }
        
        switch (this.predes.length) {
            case 0:
                this.earlyStart = 0;
                this.earlyFinish = this.earlyStart + this.duration;
                break;
            case 1:
                const Prev = getActivityObj(this.predes[0]);
                if (Prev) {
                    const va = Prev.getEarlyFinish();
                    this.earlyStart = va.earlyFinish;
                    this.earlyFinish = this.earlyStart + this.duration;
                }
                break;
            default:
                const arrs = getPrevEarlyFinishes(this.predes);
                const Prev2 = getBiggestEarlyFinish(arrs);
                const va2 = Prev2.getEarlyFinish();
                this.earlyStart = va2.earlyFinish;
                this.earlyFinish = this.earlyStart + this.duration;
                break;
        }
    }

    setLates() {
        if (this.lateStart !== -1) return;
        if (this.lateFinish === -1) {
            this.nexts.forEach(objNm => getActivityObj(objNm).setLates());
        }

        switch (this.nexts.length) {
            case 0:
                this.lateFinish = this.earlyFinish;
                this.lateStart = this.lateFinish - this.duration;
                break;
            case 1:
                const Next = getActivityObj(this.nexts[0]);
                if (Next) {
                    const va = Next.getLateStart();
                    this.lateFinish = va.lateStart;
                    this.lateStart = this.lateFinish - this.duration;
                }
                break;
            default:
                const arrs = getPrevLateStarts(this.nexts);
                const Prev = getSmallestLateStart(arrs);
                const va = Prev.getLateStart();
                this.lateFinish = va.lateStart;
                this.lateStart = this.lateFinish - this.duration;
                break;
        }
    }

    getEarlyFinish() {
        if (this.earlyFinish !== -1) {
            return { name: this.name, earlyFinish: this.earlyFinish };
        } else {
            this.setEarly();
            return { name: this.name, earlyFinish: this.earlyFinish };
        }
    }

    getLateStart() {
        if (this.lateStart !== -1) {
            return { name: this.name, lateStart: this.lateStart };
        } else {
            this.setLates();
            return { name: this.name, lateStart: this.lateStart };
        }
    }
}

function getBiggestEarlyFinish(arrs) {
    return getActivityObj(arrs.sort((a, b) => b.earlyFinish - a.earlyFinish)[0].name);
}

function getSmallestLateStart(arrs) {
    return getActivityObj(arrs.sort((a, b) => a.lateStart - b.lateStart)[0].name);
}

function getPrevEarlyFinishes(acts) {
    return acts.map(activityNm => getActivityObj(activityNm).getEarlyFinish());
}

function getPrevLateStarts(acts) {
    return acts.map(activityNm => getActivityObj(activityNm).getLateStart());
}

function checkIfPreviousStartAbsent(predes) {
    if (predes.length > 0) return true;
    return !AllActivities.some(activity => activity.predes.length === 0);
}

function addActivity(Objname, duration, preceds) {
    const isFine = checkIfPreviousStartAbsent(preceds);
    if (!isFine) return false;

    const activity = new Activity(Objname, duration);
    preceds.forEach(p => activity.addPre(p));
    AllActivities.push(activity);
}

function getActivityObj(objname) {
    return AllActivities.find(activity => activity.getName() === objname);
}

function getRootActivity() {
    const possibles = AllActivities.filter(activity => activity.countPre() === 0);
    return possibles.length === 1 ? possibles[0] : false;
}

function setForwards() {
    AllActivities.forEach(activity => {
        const nm = activity.getName();
        AllActivities.forEach(act2 => {
            if (act2.getName() !== nm && act2.predes.includes(nm)) {
                activity.addNext(act2.getName());
            }
        });
    });
}

function setFinalStopNode() {
    let nms = AllActivities.map(activity => activity.getName());
    AllActivities.forEach(activity => {
        nms = nms.filter(nm => !activity.predes.includes(nm));
    });
    return nms.length === 1 ? getActivityObj(nms[0]) : false;
}

function explainPMDiagram(Temp) {
    return AllActivities.map(Activity => Activity.reportUsingTemplate(Temp)).join('');
}

function calculatePMDiagram(Template1, Template2) {
    setForwards();
    const lastObj = setFinalStopNode();
    if (!lastObj) return 1;

    lastObj.setEarly();
    const firstObj = getRootActivity();
    if (!firstObj) return 2;

    firstObj.setLates();

    return AllActivities.map(Activity => 
        Activity.reportUsingTemplate(Activity.isOnCP() ? Template2 : Template1)
    ).join('');
}
function cleanActivities(){
    AllActivities = [];
}

/*
// Example usage
cleanActivities();
addActivity("A", 3, []);
addActivity("B", 4, ["A"]);
addActivity("C", 2, ["A"]);
addActivity("D", 5, ["B"]);
addActivity("E", 1, ["C"]);
addActivity("F", 2, ["C"]);
addActivity("G", 4, ["D", "E"]);
addActivity("H", 3, ["F", "G"]);

console.log(explainPMDiagram("Name: [N], Duration: [D], Predecessors: [PR]\n"));

const Template1 = "Name: [N], Duration: [D], Early Start: [ES], Early Finish: [EF], Late Start: [LS], Late Finish: [LF]\n";
const Template2 = "*" + Template1;
const ret = calculatePMDiagram(Template1, Template2);

if (typeof ret === 'string') {
    console.log(ret);
} else {
    switch(ret) {
        case 1: console.log("Sorry, the network is incorrect. Has multiple final endings"); break;
        case 2: console.log("Sorry, the network is incorrect. Has multiple start points"); break;
    }
}
*/
