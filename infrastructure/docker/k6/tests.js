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
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569165',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571830',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569936',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214179',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819560810',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570857',
    'https://coverservice.itkdev.dk/api/cover/pid/850290-katalog:000042567',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570840',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214175',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563682',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214177',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819565839',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:007085252',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819566136',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214176',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819566144',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819566140',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569646',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819566133',
    'https://coverservice.itkdev.dk/api/cover/pid/820030-katalog:1224871',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819568267',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819568274',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570000',
    'https://coverservice.itkdev.dk/api/cover/pid/870970-basis:45338509',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214181',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819568915',
    'https://coverservice.itkdev.dk/api/cover/pid/820010-katalog:1585148',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819552577',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819562645',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819552570',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572479',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214180',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214182',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572639',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819522337',
    'https://coverservice.itkdev.dk/api/cover/pid/820030-katalog:1402279',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570819',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570826',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571847',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214187',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569387',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819522092',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214185',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570833',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214183',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572189',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214189',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819522429',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:002325576',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819563714',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819563765',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563712',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214196',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563156',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214184',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214186',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572202',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819522146',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214190',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819568632',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569912',
    'https://coverservice.itkdev.dk/api/cover/pid/820010-katalog:444387',
    'https://coverservice.itkdev.dk/api/cover/isbn/0819552852',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214193',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819552853',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819522207',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572493',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214191',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563392',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563538',
    'https://coverservice.itkdev.dk/api/cover/pid/150008-academic:EBC776717',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571977',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214188',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571823',
    'https://coverservice.itkdev.dk/api/cover/pid/820030-katalog:133335',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572486',
    'https://coverservice.itkdev.dk/api/cover/isbn/081952185X',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819521859',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569974',
    'https://coverservice.itkdev.dk/api/cover/isbn?id=9788702272451,9780819521859,9788702284799,9788702272499',
    'https://coverservice.itkdev.dk/api/cover/pid/150008-academic:EBC776742',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819550491',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572042',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214194',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571861',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571120',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214192',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571984',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569622',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214197',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819570802',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819571021',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819572196',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214198',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569875',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214199',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819569332',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563781',
    'https://coverservice.itkdev.dk/api/cover/pid/810010-katalog:010214204',
    'https://coverservice.itkdev.dk/api/cover/pid/850290-katalog:000040339',
    'https://coverservice.itkdev.dk/api/cover/isbn/9780819563071',
    'https://coverservice.itkdev.dk/api/cover/isbn/9788702272451'
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
