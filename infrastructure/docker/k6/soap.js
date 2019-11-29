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

function envelope() {
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
        '         <mor:pidList>870970-basis:29506914|870970-basis:29506906</mor:pidList>' +
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
