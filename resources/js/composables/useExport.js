import { ref } from 'vue';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

export function useExport(dataA, dataB, isCompareMode, period1Label, period2Label) {
    const exporting = ref(false);

    // 辅助函数：获取本地日期字符串 (YYYY-MM-DD)
    const getLocalDateString = () => {
        const date = new Date();
        const offset = date.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(date - offset)).toISOString().slice(0, 10);
        return localISOTime;
    };

    // Excel 导出逻辑
    const exportToExcel = () => {
        const wb = XLSX.utils.book_new();

        // --- 1. 构建 Summary Sheet ---
        const summaryRows = [];

        // Header Info
        summaryRows.push(["Report Generated", new Date().toLocaleString()]);
        summaryRows.push(["Mode", isCompareMode.value ? "Comparison" : "Single Period"]);
        summaryRows.push(["Current Period", period1Label.value]);

        if (isCompareMode.value) {
            summaryRows.push(["Comparison Period", period2Label.value]);
            // 在这里添加公式说明，放在比较显眼的地方
            summaryRows.push(["Note", "Change = Current - Comparison"]);
        }

        summaryRows.push([]); // 空行

        // Metrics Table Header
        const metricHeader = ["Metric", "Current Period"];
        if (isCompareMode.value) {
            metricHeader.push("Comparison Period", "Change");
        }
        summaryRows.push(metricHeader);

        // Metrics Data Helper (防止数据为空报错)
        const getStat = (source, key) => source?.stats?.[key] ?? 0;

        // Metrics Rows
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
        // 设置列宽
        wsSummary['!cols'] = [{ wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 15 }];
        XLSX.utils.book_append_sheet(wb, wsSummary, "Summary");


        // --- 2. 构建 Intents Sheet ---
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
        // 按当前周期数量降序排列
        intentRows.sort((a, b) => b["Current Period"] - a["Current Period"]);
        XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(intentRows), "Intents");


        // --- 3. 构建 Departments Sheet ---
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


        // --- 4. 构建 FAQs Sheet ---
        const faqRows = [];
        const listA = dataA.faqs?.Faq || [];
        const listB = dataB.faqs?.Faq || [];
        const maxLen = Math.max(listA.length, isCompareMode.value ? listB.length : 0);

        for (let i = 0; i < maxLen; i++) {
            const row = {};

            // Period A
            if (listA[i]) {
                row["Rank"] = i + 1; // 共用 Rank
                row["Current Question"] = listA[i].question;
                row["Current Count"] = listA[i].total;
            }

            // Period B (Comparison)
            if (isCompareMode.value) {
                row["|"] = ""; // 分隔列
                if (listB[i]) {
                    row["Comparison Question"] = listB[i].question;
                    row["Comparison Count"] = listB[i].total;
                }
            }
            faqRows.push(row);
        }

        const wsFaq = XLSX.utils.json_to_sheet(faqRows);
        // 设置 FAQ 列宽，让问题列宽一点
        wsFaq['!cols'] = [{ wch: 5 }, { wch: 50 }, { wch: 10 }, { wch: 2 }, { wch: 50 }, { wch: 10 }];
        XLSX.utils.book_append_sheet(wb, wsFaq, "FAQs");

        // 导出文件
        const fileName = `Report_${getLocalDateString()}.xlsx`;
        XLSX.writeFile(wb, fileName);
    };


    // PDF 导出逻辑
    const exportToPDF = async (elementId) => {
        exporting.value = true; // 在 Dashboard.vue 中控制 Header 显示

        // 等待 Vue DOM 更新 (渲染出 Header)
        await new Promise(r => setTimeout(r, 800)); // 稍微增加延时，确保 Chart.js 渲染完全

        const element = document.getElementById(elementId);
        if (!element) {
            console.error(`Element #${elementId} not found`);
            exporting.value = false;
            return;
        }

        try {
            const canvas = await html2canvas(element, {
                scale: 2, // 提高清晰度
                useCORS: true,
                backgroundColor: '#ffffff', // 确保背景是白色，不是透明
                logging: false
            });

            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');

            const pdfW = pdf.internal.pageSize.getWidth();
            const pdfH = pdf.internal.pageSize.getHeight();

            // 按比例计算图片在 PDF 中的高度
            const imgH = (canvas.height * pdfW) / canvas.width;

            let heightLeft = imgH;
            let position = 0;

            // 第一页
            pdf.addImage(imgData, 'PNG', 0, position, pdfW, imgH);
            heightLeft -= pdfH;

            // 如果内容过长，自动分页
            while (heightLeft > 0) {
                position = heightLeft - imgH; // 这里的逻辑是每次向上移动图片位置
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, - (imgH - heightLeft), pdfW, imgH);
                // 注意：多页 PDF 截图通常比较复杂，简单的 addImage 可能会截断文字。
                // 这里的简单算法：position 应该是负数，把图片往上推。
                // 修正后的简单分页算法：
                // 实际上对于简单的 Dashboard，通常建议缩小内容放入一页，或者接受简单的截断。
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
