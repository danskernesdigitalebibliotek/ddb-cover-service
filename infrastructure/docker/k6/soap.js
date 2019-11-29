import http from "k6/http";
import { check } from "k6";

export let options = {
    stages: [
        // Ramp-up
        { duration: "1m", target: 50 },

        { duration: "2m", target: 100 },
        { duration: "2m", target: 250 },

        // Ramp-down
        { duration: "1m", target: 0 }
    ],
    insecureSkipTLSVerify: true,
    noUsageReport: true,
    thresholds: {
        http_req_duration: ["avg<200"]
    },
    noConnectionReuse: true,
    userAgent: "MyK6UserAgentString/1.0"
};

let pids = [
    '870970-basis:29506914|870970-basis:29506906',
    '870970-basis:43885588|870970-basis:54970056',
    '870970-basis:52377811|870970-basis:27033210|870970-basis:27636195|870970-basis:53629660',
    '810010-katalog:009183355__1|870970-basis:24280195|870970-basis:52874076',
    '870970-basis:27415822|870970-basis:51972813',
    '870970-basis:25362578|870970-basis:23740052|870970-basis:25828941',
    '870970-basis:26615682|870970-basis:28095511',
    '870970-basis:25750292|870970-basis:28696418|870970-basis:29212848',
    '870970-basis:28070713|870970-basis:25954173',
    '870970-basis:25084322|870970-basis:27218172',
    '870970-basis:26908566|870970-basis:23019043',
    '870970-basis:51719476|870970-basis:25504488|870970-basis:26410401',
    '870970-basis:23471310|830080-katalog:000181550',
    '870970-basis:26548438|870970-basis:28314450',
    '870970-basis:26003970|870970-basis:28601727|870970-basis:26358388|870970-basis:21497126|870970-basis:25040457',
    '870970-basis:24264955|870970-basis:26737656',
    '870970-basis:27662331|870970-basis:26846978',
    '870970-basis:53216714|870970-basis:26995450',
    '870970-basis:25986873|870970-basis:24914402',
    '870970-basis:52387167|870970-basis:53082181|870970-basis:26532973|870970-basis:27928137',
    '870970-basis:29489297|870970-basis:51588266|870970-basis:22580566',
    '870970-basis:27078761|870970-basis:21424781',
    '870970-basis:25679083|870970-basis:22579940',
    '820010-katalog:2265836|870970-basis:53393543|870970-basis:26089743|870970-basis:22580760',
    '870970-basis:50801101|870970-basis:54161832',
    '870970-basis:45965929|870970-basis:53967264',
    '870970-basis:21660396|870970-basis:27290531|870970-basis:52890624',
    '870970-basis:27602517|870970-basis:29630496',
    '870970-basis:51847636|870970-basis:25815203',
    '870970-basis:22659111|870970-basis:28727119|870970-basis:52482429|870970-basis:26340721',
    '870970-basis:29412022|870970-basis:25726898',
    '870970-basis:24687619|870970-basis:52110971',
    '870970-basis:24905462|870970-basis:21562742',
    '870970-basis:28818769|870970-basis:28129009|870970-basis:24509559',
    '870970-basis:50789276|870970-basis:25821963',
    '870970-basis:25986946|870970-basis:23832976',
    '870970-basis:53949738|870970-basis:29919887|870970-basis:27587992',
    '870970-basis:21739235|870970-basis:25329767',
    '870970-basis:27342906|870970-basis:24891143|820030-katalog:429806',
    '870970-basis:28003951|870970-basis:22679457',
    '870970-basis:25900715|870970-basis:51724348'
];

function envelope() {
    let pidList = pids[Math.floor(Math.random() * pids.length)];

    return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mor="https://cover.dandigbib.org/ns/moreinfo">' +
        '   <soapenv:Header/>' +
        '   <soapenv:Body>' +
        '      <mor:moreInfoRequest>' +
        '         <mor:authentication>' +
        '            <mor:authenticationUser>test</mor:authenticationUser>' +
        '            <mor:authenticationGroup>100200</mor:authenticationGroup>' +
        '            <mor:authenticationPassword>test</mor:authenticationPassword>' +
        '         </mor:authentication>' +
        '         <mor:identifier>' +
        '         <mor:pidList> + pidList + </mor:pidList>' +
        '         </mor:identifier>' +
        '         <mor:identifier>' +
        '         <mor:pid>882330-basis:17154889</mor:pid>' +
        '         </mor:identifier>' +
        '         <mor:identifier>' +
        '         <mor:isbn>9788740602456</mor:isbn>' +
        '         </mor:identifier>' +
        '      </mor:moreInfoRequest>' +
        '   </soapenv:Body>' +
        '</soapenv:Envelope>';
}

export default function() {
    let params = {
        headers: {
            "Content-Type": "application/soap+xml"
        }
    };
    let url = 'https://cover.dandigbib.org/2.11/';
    let res = http.post(url, envelope(), params);
    check(res, {
        "status was 200": (r) => r.status === 200,
        "transaction time OK": (r) => r.timings.duration < 200
    });
}
