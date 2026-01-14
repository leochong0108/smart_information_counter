import { ref } from 'vue';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

export function useExport(dataA, dataB, isCompareMode, period1Label, period2Label) {
    const exporting = ref(false);

    const getLocalDateString = () => {
        const date = new Date();
        const offset = date.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(date - offset)).toISOString().slice(0, 10);
        return localISOTime;
    };

    const exportToExcel = () => {
        const wb = XLSX.utils.book_new();

        const summaryRows = [];

        summaryRows.push(["Report Generated", new Date().toLocaleString()]);
        summaryRows.push(["Mode", isCompareMode.value ? "Comparison" : "Single Period"]);
        summaryRows.push(["Current Period", period1Label.value]);

        if (isCompareMode.value) {
            summaryRows.push(["Comparison Period", period2Label.value]);
            summaryRows.push(["Note", "Change = Current - Comparison"]);
        }

        summaryRows.push([]);

        const metricHeader = ["Metric", "Current Period"];
        if (isCompareMode.value) {
            metricHeader.push("Comparison Period", "Change");
        }
        summaryRows.push(metricHeader);

        const getStat = (source, key) => source?.stats?.[key] ?? 0;

        const metrics = [
            { label: "Total Queries", key: "totalQuestions" },
            { label: "Success", key: "totalSuccess" },
            { label: "Failed", key: "totalFail" }
        ];

        metrics.forEach(m => {
            const valA = getStat(dataA, m.key);
            const row = [m.label, valA];

            if (isCompareMode.value) {
                const valB = getStat(dataB, m.key);
                row.push(valB, valA - valB);
            }
            summaryRows.push(row);
        });

        const wsSummary = XLSX.utils.aoa_to_sheet(summaryRows);
        wsSummary['!cols'] = [{ wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 15 }];
        XLSX.utils.book_append_sheet(wb, wsSummary, "Summary");


        const allIntents = new Set([
            ...(dataA.faqs?.Intent || []).map(i => i.intent_name),
            ...(isCompareMode.value ? (dataB.faqs?.Intent || []).map(i => i.intent_name) : [])
        ]);

        const intentRows = Array.from(allIntents).map(name => {
            const valA = (dataA.faqs?.Intent || []).find(i => i.intent_name === name)?.total || 0;
            const row = { "Intent": name, "Current Period": valA };

            if (isCompareMode.value) {
                const valB = (dataB.faqs?.Intent || []).find(i => i.intent_name === name)?.total || 0;
                row["Comparison Period"] = valB;
                row["Change"] = valA - valB;
            }
            return row;
        });

        intentRows.sort((a, b) => b["Current Period"] - a["Current Period"]);
        XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(intentRows), "Intents");


        const allDepts = new Set([
            ...(dataA.faqs?.Department || []).map(d => d.name),
            ...(isCompareMode.value ? (dataB.faqs?.Department || []).map(d => d.name) : [])
        ]);

        const deptRows = Array.from(allDepts).map(name => {
            const valA = (dataA.faqs?.Department || []).find(d => d.name === name)?.total || 0;
            const row = { "Department": name, "Current Period": valA };

            if (isCompareMode.value) {
                const valB = (dataB.faqs?.Department || []).find(d => d.name === name)?.total || 0;
                row["Comparison Period"] = valB;
                row["Change"] = valA - valB;
            }
            return row;
        });
        deptRows.sort((a, b) => b["Current Period"] - a["Current Period"]);
        XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(deptRows), "Departments");


        const faqRows = [];
        const listA = dataA.faqs?.Faq || [];
        const listB = dataB.faqs?.Faq || [];
        const maxLen = Math.max(listA.length, isCompareMode.value ? listB.length : 0);

        for (let i = 0; i < maxLen; i++) {
            const row = {};

            if (listA[i]) {
                row["Rank"] = i + 1;
                row["Current Question"] = listA[i].question;
                row["Current Count"] = listA[i].total;
            }


            if (isCompareMode.value) {
                row["|"] = "";
                if (listB[i]) {
                    row["Comparison Question"] = listB[i].question;
                    row["Comparison Count"] = listB[i].total;
                }
            }
            faqRows.push(row);
        }

        const wsFaq = XLSX.utils.json_to_sheet(faqRows);

        wsFaq['!cols'] = [{ wch: 5 }, { wch: 50 }, { wch: 10 }, { wch: 2 }, { wch: 50 }, { wch: 10 }];
        XLSX.utils.book_append_sheet(wb, wsFaq, "FAQs");


        const fileName = `Report_${getLocalDateString()}.xlsx`;
        XLSX.writeFile(wb, fileName);
    };


    const exportToPDF = async (elementId) => {
        exporting.value = true;


        await new Promise(r => setTimeout(r, 800));

        const element = document.getElementById(elementId);
        if (!element) {
            console.error(`Element #${elementId} not found`);
            exporting.value = false;
            return;
        }

        try {
            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            });

            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');

            const pdfW = pdf.internal.pageSize.getWidth();
            const pdfH = pdf.internal.pageSize.getHeight();

            const imgH = (canvas.height * pdfW) / canvas.width;

            let heightLeft = imgH;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, pdfW, imgH);
            heightLeft -= pdfH;

            while (heightLeft > 0) {
                position = heightLeft - imgH;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, - (imgH - heightLeft), pdfW, imgH);

                heightLeft -= pdfH;
            }

            const fileName = `Report_${getLocalDateString()}.pdf`;
            pdf.save(fileName);

        } catch (e) {
            console.error("PDF Export Error:", e);
            alert("Failed to export PDF. Please check console for details.");
        } finally {
            exporting.value = false;
        }
    };

    return {
        exporting,
        exportToExcel,
        exportToPDF,
    };
}
