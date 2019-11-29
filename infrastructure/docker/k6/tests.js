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

export let urls = [
    'https://cover.dandigbib.org/api/cover/isbn/9780819569165',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571830',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569936',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214179',
    'https://cover.dandigbib.org/api/cover/isbn/9780819560810',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570857',
    'https://cover.dandigbib.org/api/cover/pid/850290-katalog:000042567',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570840',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214175',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563682',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214177',
    'https://cover.dandigbib.org/api/cover/isbn/9780819565839',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:007085252',
    'https://cover.dandigbib.org/api/cover/isbn/0819566136',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214176',
    'https://cover.dandigbib.org/api/cover/isbn/0819566144',
    'https://cover.dandigbib.org/api/cover/isbn/9780819566140',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569646',
    'https://cover.dandigbib.org/api/cover/isbn/9780819566133',
    'https://cover.dandigbib.org/api/cover/pid/820030-katalog:1224871',
    'https://cover.dandigbib.org/api/cover/isbn/9780819568267',
    'https://cover.dandigbib.org/api/cover/isbn/9780819568274',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570000',
    'https://cover.dandigbib.org/api/cover/pid/870970-basis:45338509',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214181',
    'https://cover.dandigbib.org/api/cover/isbn/9780819568915',
    'https://cover.dandigbib.org/api/cover/pid/820010-katalog:1585148',
    'https://cover.dandigbib.org/api/cover/isbn/0819552577',
    'https://cover.dandigbib.org/api/cover/isbn/0819562645',
    'https://cover.dandigbib.org/api/cover/isbn/9780819552570',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572479',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214180',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214182',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572639',
    'https://cover.dandigbib.org/api/cover/isbn/9780819522337',
    'https://cover.dandigbib.org/api/cover/pid/820030-katalog:1402279',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570819',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570826',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571847',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214187',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569387',
    'https://cover.dandigbib.org/api/cover/isbn/9780819522092',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214185',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570833',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214183',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572189',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214189',
    'https://cover.dandigbib.org/api/cover/isbn/9780819522429',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:002325576',
    'https://cover.dandigbib.org/api/cover/isbn/0819563714',
    'https://cover.dandigbib.org/api/cover/isbn/0819563765',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563712',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214196',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563156',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214184',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214186',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572202',
    'https://cover.dandigbib.org/api/cover/isbn/9780819522146',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214190',
    'https://cover.dandigbib.org/api/cover/isbn/9780819568632',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569912',
    'https://cover.dandigbib.org/api/cover/pid/820010-katalog:444387',
    'https://cover.dandigbib.org/api/cover/isbn/0819552852',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214193',
    'https://cover.dandigbib.org/api/cover/isbn/9780819552853',
    'https://cover.dandigbib.org/api/cover/isbn/9780819522207',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572493',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214191',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563392',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563538',
    'https://cover.dandigbib.org/api/cover/pid/150008-academic:EBC776717',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571977',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214188',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571823',
    'https://cover.dandigbib.org/api/cover/pid/820030-katalog:133335',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572486',
    'https://cover.dandigbib.org/api/cover/isbn/081952185X',
    'https://cover.dandigbib.org/api/cover/isbn/9780819521859',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569974',
    'https://cover.dandigbib.org/api/cover/isbn?id=9788702272451,9780819521859,9788702284799,9788702272499',
    'https://cover.dandigbib.org/api/cover/pid/150008-academic:EBC776742',
    'https://cover.dandigbib.org/api/cover/isbn/9780819550491',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572042',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214194',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571861',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571120',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214192',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571984',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569622',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214197',
    'https://cover.dandigbib.org/api/cover/isbn/9780819570802',
    'https://cover.dandigbib.org/api/cover/isbn/9780819571021',
    'https://cover.dandigbib.org/api/cover/isbn/9780819572196',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214198',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569875',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214199',
    'https://cover.dandigbib.org/api/cover/isbn/9780819569332',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563781',
    'https://cover.dandigbib.org/api/cover/pid/810010-katalog:010214204',
    'https://cover.dandigbib.org/api/cover/pid/850290-katalog:000040339',
    'https://cover.dandigbib.org/api/cover/isbn/9780819563071',
    'https://cover.dandigbib.org/api/cover/isbn/9788702272451'
];

export default function() {
    let params = { headers: { "accept": "application/json" } };
    let url = urls[Math.floor(Math.random() * urls.length)];
    let res = http.get(url, params);
    check(res, {
        "status was 200": (r) => r.status === 200,
        "transaction time OK": (r) => r.timings.duration < 200
    });
}
